<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SharePointService
{
    public function token(): string
    {
        return Cache::remember('ms_graph_token', 3300, function () {
            $tenant = config('services.ms.tenant_id');
            $res = Http::asForm()->post("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
                'client_id'     => config('services.ms.client_id'),
                'client_secret' => config('services.ms.client_secret'),
                'scope'         => 'https://graph.microsoft.com/.default',
                'grant_type'    => 'client_credentials',
            ]);

            if (!$res->successful()) {
                throw new \Exception('MS token failed: '.$res->body());
            }

            return $res->json()['access_token'];
        });
    }

    public function siteId(): string
    {
        return Cache::remember('sp_site_id', 86400, function () {
            $host = config('services.sharepoint.site_hostname');
            $path = config('services.sharepoint.site_path');

            $res = Http::withToken($this->token())
                ->get("https://graph.microsoft.com/v1.0/sites/{$host}:{$path}");

            if (!$res->successful()) {
                throw new \Exception('Site lookup failed: '.$res->body());
            }

            return $res->json()['id'];
        });
    }

    public function driveId(): string
    {
        return Cache::remember('sp_drive_id', 86400, function () {
            $siteId = $this->siteId();
            $library = config('services.sharepoint.library_name');

            $res = Http::withToken($this->token())
                ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives");

            if (!$res->successful()) {
                throw new \Exception('Drives list failed: '.$res->body());
            }

            $drive = collect($res->json()['value'] ?? [])
                ->first(fn($d) => strtolower($d['name'] ?? '') === strtolower($library));

            if (!$drive) {
                throw new \Exception("Drive/Library not found: {$library}");
            }

            return $drive['id'];
        });
    }

    public function ensureFolder(string $parentPath, string $folderName): string
    {
        $driveId = $this->driveId();
        $parentPath = trim($parentPath, '/');
        $folderName = trim($folderName);

        // Create folder using children endpoint
        $url = $parentPath === ''
            ? "https://graph.microsoft.com/v1.0/drives/{$driveId}/root/children"
            : "https://graph.microsoft.com/v1.0/drives/{$driveId}/root:/{$parentPath}:/children";

        $res = Http::withToken($this->token())->post($url, [
            'name' => $folderName,
            'folder' => new \stdClass(),
            '@microsoft.graph.conflictBehavior' => 'fail',
        ]);

        // 201 created OR 409 already exists
        if ($res->status() === 409) {
            return ($parentPath ? $parentPath.'/' : '').$folderName;
        }

        if (!$res->successful()) {
            throw new \Exception('Folder create failed: '.$res->body());
        }

        return ($parentPath ? $parentPath.'/' : '').$folderName;
    }

    public function uploadSmallFile(string $folderPath, string $fileName, string $absoluteFilePath): array
    {
        // Note: <= 4MB PUT /content works. Your limit is 20MB, so later we’ll switch to upload session.
        $driveId = $this->driveId();
        $folderPath = trim($folderPath, '/');

        $graphPath = $folderPath === ''
            ? "root:/{$fileName}:/content"
            : "root:/{$folderPath}/{$fileName}:/content";

        $res = Http::timeout(300)
            ->withToken($this->token())
            ->withBody(file_get_contents($absoluteFilePath), 'application/octet-stream')
            ->put("https://graph.microsoft.com/v1.0/drives/{$driveId}/{$graphPath}");

        if (!$res->successful()) {
            throw new \Exception('Upload failed: '.$res->body());
        }

        return $res->json(); // contains webUrl, id, parentReference, etc.
    }

    public function uploadLargeFile(string $folderPath, string $fileName, string $absoluteFilePath): array
    {
        $driveId = $this->driveId();
        $folderPath = trim($folderPath, '/');

        // 1) Create upload session
        $graphPath = $folderPath === ''
            ? "root:/{$fileName}:/createUploadSession"
            : "root:/{$folderPath}/{$fileName}:/createUploadSession";

        $sessionRes = Http::timeout(300)
            ->withToken($this->token())
            ->post("https://graph.microsoft.com/v1.0/drives/{$driveId}/{$graphPath}", [
                'item' => [
                    '@microsoft.graph.conflictBehavior' => 'replace',
                    'name' => $fileName,
                ],
            ]);

        if (!$sessionRes->successful()) {
            throw new \Exception('Create upload session failed: ' . $sessionRes->body());
        }

        $uploadUrl = $sessionRes->json()['uploadUrl'] ?? null;
        if (!$uploadUrl) {
            throw new \Exception('UploadUrl missing from session response.');
        }

        // 2) Upload in chunks
        $fileSize  = filesize($absoluteFilePath);
        $handle    = fopen($absoluteFilePath, 'rb');

        // 5MB chunk (Graph friendly)
        $chunkSize = 5 * 1024 * 1024;
        $start     = 0;

        while (!feof($handle)) {
            $data = fread($handle, $chunkSize);
            $end  = $start + strlen($data) - 1;

            $chunkRes = Http::timeout(120)
                ->withHeaders([
                    'Content-Length' => (string) strlen($data),
                    'Content-Range'  => "bytes {$start}-{$end}/{$fileSize}",
                ])
                ->withBody($data, 'application/octet-stream')
                ->put($uploadUrl);

            // 202 = chunk accepted (more to come)
            // 201/200 = upload complete with file metadata
            if (in_array($chunkRes->status(), [200, 201], true)) {
                fclose($handle);
                return $chunkRes->json();
            }

            if ($chunkRes->status() !== 202) {
                fclose($handle);
                throw new \Exception('Chunk upload failed: ' . $chunkRes->status() . ' ' . $chunkRes->body());
            }

            $start = $end + 1;
        }

        fclose($handle);
        throw new \Exception('Upload did not complete (unexpected EOF).');
    }

    public function safeName(string $name): string
    {
        $name = Str::of($name)->replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], ' ');
        $name = Str::of($name)->squish()->trim();
        $name = Str::of($name)->replace(' ', '_');
        return (string) $name;
    }

    public function streamByItemId(string $itemId, string $downloadName = 'file', bool $inline = false)
    {
        $driveId = $this->driveId();

        $url = "https://graph.microsoft.com/v1.0/drives/{$driveId}/items/{$itemId}/content";

        $res = Http::timeout(300)
            ->withToken($this->token())
            ->withOptions(['stream' => true])
            ->get($url);

        if (!$res->successful()) {
            throw new \Exception('Unable to fetch file from SharePoint: ' . $res->status() . ' ' . $res->body());
        }

        $psr  = $res->toPsrResponse();
        $body = $psr->getBody();

        $contentType = $res->header('Content-Type') ?? 'application/octet-stream';
        $disposition = $inline ? 'inline' : 'attachment';

        return response()->streamDownload(function () use ($body) {
            while (!$body->eof()) {
                echo $body->read(1024 * 64); // 64KB chunks
                if (function_exists('flush')) flush();
            }
        }, $downloadName, [
            'Content-Type'        => $contentType,
            'Content-Disposition' => $disposition . '; filename="' . addslashes($downloadName) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
    public function temporaryDownloadUrlByItemId(string $itemId): string
    {
        $driveId = $this->driveId();

        // Graph returns @microsoft.graph.downloadUrl on item
        $res = Http::withToken($this->token())
            ->get("https://graph.microsoft.com/v1.0/drives/{$driveId}/items/{$itemId}", [
                '$select' => 'id,name,@microsoft.graph.downloadUrl',
            ]);

        if (!$res->successful()) {
            throw new \Exception('Unable to get downloadUrl: ' . $res->status() . ' ' . $res->body());
        }

        $url = $res->json()['@microsoft.graph.downloadUrl'] ?? null;

        if (!$url) {
            throw new \Exception('downloadUrl missing from Graph response.');
        }

        return $url;
    }
}
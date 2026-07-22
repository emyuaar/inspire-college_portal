<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SharePointService
{
    public function token(): string
    {
        $tenant = $this->configValue(['services.sharepoint.tenant_id', 'services.ms.tenant_id']);
        $clientId = $this->configValue(['services.sharepoint.client_id', 'services.ms.client_id']);
        $clientSecret = $this->configValue(['services.sharepoint.client_secret', 'services.ms.client_secret']);

        if (!$tenant || !$clientId || !$clientSecret) {
            throw new \RuntimeException('SharePoint credentials are not configured.');
        }

        $cacheKey = 'sharepoint_graph_token_' . md5($tenant . '|' . $clientId);

        return Cache::remember($cacheKey, 3300, function () use ($tenant, $clientId, $clientSecret) {
            $res = Http::asForm()->post("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]);

            if (!$res->successful()) {
                throw new \RuntimeException('MS token failed: ' . $res->body());
            }

            return $res->json('access_token');
        });
    }

    public function siteId(): string
    {
        if ($siteId = config('services.sharepoint.site_id')) {
            return $siteId;
        }

        $host = config('services.sharepoint.site_hostname');
        $path = config('services.sharepoint.site_path');

        if (!$host || !$path) {
            throw new \RuntimeException('SharePoint site hostname/path are not configured.');
        }

        $cacheKey = 'sharepoint_site_id_' . md5($host . '|' . $path);

        return Cache::remember($cacheKey, 86400, function () use ($host, $path) {
            $res = Http::withToken($this->token())
                ->get("https://graph.microsoft.com/v1.0/sites/{$host}:{$path}");

            if (!$res->successful()) {
                throw new \RuntimeException('Site lookup failed: ' . $res->body());
            }

            return $res->json('id');
        });
    }

    public function driveId(): string
    {
        if ($driveId = config('services.sharepoint.drive_id')) {
            return $driveId;
        }

        $siteId = $this->siteId();
        $library = config('services.sharepoint.library_name', 'Submissions');
        $cacheKey = 'sharepoint_drive_id_' . md5($siteId . '|' . $library);

        return Cache::remember($cacheKey, 86400, function () use ($siteId, $library) {
            $res = Http::withToken($this->token())
                ->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives");

            if (!$res->successful()) {
                throw new \RuntimeException('Drives list failed: ' . $res->body());
            }

            $drive = collect($res->json('value') ?? [])
                ->first(fn ($d) => strtolower($d['name'] ?? '') === strtolower($library));

            if (!$drive) {
                $available = collect($res->json('value') ?? [])->pluck('name')->implode(', ');
                throw new \RuntimeException("Drive/Library not found: {$library}. Available: {$available}");
            }

            return $drive['id'];
        });
    }

    public function ensureFolder(string $parentPath, string $folderName): string
    {
        $driveId = $this->driveId();
        $parentPath = trim($parentPath, '/');
        $folderName = trim($folderName);

        $url = $parentPath === ''
            ? "https://graph.microsoft.com/v1.0/drives/{$driveId}/root/children"
            : "https://graph.microsoft.com/v1.0/drives/{$driveId}/root:/{$parentPath}:/children";

        $res = Http::withToken($this->token())->post($url, [
            'name' => $folderName,
            'folder' => new \stdClass(),
            '@microsoft.graph.conflictBehavior' => 'fail',
        ]);

        if ($res->status() === 409) {
            return ($parentPath ? $parentPath . '/' : '') . $folderName;
        }

        if (!$res->successful()) {
            throw new \RuntimeException('Folder create failed: ' . $res->body());
        }

        return ($parentPath ? $parentPath . '/' : '') . $folderName;
    }

    public function uploadSmallFile(string $folderPath, string $fileName, string $absoluteFilePath): array
    {
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
            throw new \RuntimeException('Upload failed: ' . $res->body());
        }

        return $res->json();
    }

    public function uploadLargeFile(string $folderPath, string $fileName, string $absoluteFilePath): array
    {
        $driveId = $this->driveId();
        $folderPath = trim($folderPath, '/');

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
            throw new \RuntimeException('Create upload session failed: ' . $sessionRes->body());
        }

        $uploadUrl = $sessionRes->json('uploadUrl');
        if (!$uploadUrl) {
            throw new \RuntimeException('UploadUrl missing from session response.');
        }

        $fileSize = filesize($absoluteFilePath);
        $handle = fopen($absoluteFilePath, 'rb');
        $chunkSize = 5 * 1024 * 1024;
        $start = 0;

        try {
            while (!feof($handle)) {
                $data = fread($handle, $chunkSize);
                $end = $start + strlen($data) - 1;

                $chunkRes = Http::timeout(120)
                    ->withHeaders([
                        'Content-Length' => (string) strlen($data),
                        'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
                    ])
                    ->withBody($data, 'application/octet-stream')
                    ->put($uploadUrl);

                if (in_array($chunkRes->status(), [200, 201], true)) {
                    return $chunkRes->json();
                }

                if ($chunkRes->status() !== 202) {
                    throw new \RuntimeException('Chunk upload failed: ' . $chunkRes->status() . ' ' . $chunkRes->body());
                }

                $start = $end + 1;
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        throw new \RuntimeException('Upload did not complete.');
    }

    public function uploadFile(string $folderPath, string $fileName, string $absoluteFilePath): array
    {
        return filesize($absoluteFilePath) <= 3.5 * 1024 * 1024
            ? $this->uploadSmallFile($folderPath, $fileName, $absoluteFilePath)
            : $this->uploadLargeFile($folderPath, $fileName, $absoluteFilePath);
    }

    public function safeName(string $name): string
    {
        $name = Str::of($name)->replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], ' ');
        $name = Str::of($name)->squish()->trim();
        $name = Str::of($name)->replace(' ', '_');

        return (string) $name;
    }

    public function streamByItemId(string $itemId, string $downloadName = 'file', bool $inline = false, ?string $driveId = null)
    {
        $driveId = $driveId ?: $this->driveId();
        $metadataName = $this->extensionFromName($downloadName) ? null : $this->driveItemName($driveId, $itemId);

        $res = Http::timeout(300)
            ->withToken($this->token())
            ->withOptions(['stream' => true])
            ->get("https://graph.microsoft.com/v1.0/drives/{$driveId}/items/{$itemId}/content");

        if (!$res->successful()) {
            throw new \RuntimeException('Unable to fetch file from SharePoint: ' . $res->status() . ' ' . $res->body());
        }

        $body = $res->toPsrResponse()->getBody();
        $contentType = $res->header('Content-Type') ?? 'application/octet-stream';
        $downloadName = $this->downloadNameWithExtension($downloadName, $contentType, $metadataName, $res->header('Content-Disposition'));
        $disposition = $inline ? 'inline' : 'attachment';

        return response()->streamDownload(function () use ($body) {
            while (!$body->eof()) {
                echo $body->read(1024 * 64);
                if (function_exists('flush')) {
                    flush();
                }
            }
        }, $downloadName, [
            'Content-Type' => $contentType,
            'Content-Disposition' => $disposition . '; filename="' . addslashes($downloadName) . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function temporaryDownloadUrlByItemId(string $itemId, ?string $driveId = null): string
    {
        $driveId = $driveId ?: $this->driveId();

        $res = Http::withToken($this->token())
            ->get("https://graph.microsoft.com/v1.0/drives/{$driveId}/items/{$itemId}", [
                '$select' => 'id,name,@microsoft.graph.downloadUrl',
            ]);

        if (!$res->successful()) {
            throw new \RuntimeException('Unable to get downloadUrl: ' . $res->status() . ' ' . $res->body());
        }

        $url = $res->json('@microsoft.graph.downloadUrl');
        if (!$url) {
            throw new \RuntimeException('downloadUrl missing from Graph response.');
        }

        return $url;
    }

    public function streamByShareUrl(string $sharingUrl, string $downloadName = 'file', bool $inline = false)
    {
        $encodedUrl = 'u!' . rtrim(strtr(base64_encode($sharingUrl), '+/', 'u_'), '=');
        $metadataName = $this->extensionFromName($downloadName) ? null : $this->sharedDriveItemName($encodedUrl);

        $res = Http::timeout(300)
            ->withToken($this->token())
            ->withOptions(['stream' => true])
            ->get("https://graph.microsoft.com/v1.0/shares/{$encodedUrl}/driveItem/content");

        if (!$res->successful()) {
            throw new \RuntimeException('Unable to fetch shared file from SharePoint: ' . $res->status() . ' ' . $res->body());
        }

        $body = $res->toPsrResponse()->getBody();
        $contentType = $res->header('Content-Type') ?? 'application/octet-stream';
        $downloadName = $this->downloadNameWithExtension($downloadName, $contentType, $metadataName, $res->header('Content-Disposition'));
        $disposition = $inline ? 'inline' : 'attachment';

        return response()->streamDownload(function () use ($body) {
            while (!$body->eof()) {
                echo $body->read(1024 * 64);
                if (function_exists('flush')) {
                    flush();
                }
            }
        }, $downloadName, [
            'Content-Type' => $contentType,
            'Content-Disposition' => $disposition . '; filename="' . addslashes($downloadName) . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function driveItemName(string $driveId, string $itemId): ?string
    {
        try {
            $res = Http::timeout(30)
                ->withToken($this->token())
                ->get("https://graph.microsoft.com/v1.0/drives/{$driveId}/items/{$itemId}", [
                    '$select' => 'name',
                ]);

            return $res->successful() ? $res->json('name') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function sharedDriveItemName(string $encodedUrl): ?string
    {
        try {
            $res = Http::timeout(30)
                ->withToken($this->token())
                ->get("https://graph.microsoft.com/v1.0/shares/{$encodedUrl}/driveItem", [
                    '$select' => 'name',
                ]);

            return $res->successful() ? $res->json('name') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function downloadNameWithExtension(string $downloadName, ?string $contentType, ?string ...$sources): string
    {
        $downloadName = trim($downloadName) !== '' ? trim($downloadName) : 'file';
        if ($this->extensionFromName($downloadName)) {
            return $downloadName;
        }

        foreach ($sources as $source) {
            $source = $this->filenameFromContentDisposition($source) ?: $source;
            $extension = $this->extensionFromName($source);
            if ($extension) {
                return $downloadName . '.' . $extension;
            }
        }

        $extension = $this->extensionFromMime($contentType);
        return $extension ? $downloadName . '.' . $extension : $downloadName;
    }

    private function filenameFromContentDisposition(?string $contentDisposition): ?string
    {
        if (!$contentDisposition) {
            return null;
        }

        if (preg_match("~filename\*=UTF-8''([^;]+)~i", $contentDisposition, $match)) {
            return rawurldecode(trim($match[1], ' \"'));
        }

        if (preg_match('~filename="?([^";]+)"?~i', $contentDisposition, $match)) {
            return trim($match[1]);
        }

        return null;
    }

    private function extensionFromName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $path = parse_url($name, PHP_URL_PATH) ?: $name;
        $extension = strtolower((string) pathinfo(rawurldecode($path), PATHINFO_EXTENSION));

        return preg_match('/^[a-z0-9]{1,10}$/', $extension) ? $extension : null;
    }

    private function extensionFromMime(?string $contentType): ?string
    {
        $contentType = strtolower(trim(explode(';', (string) $contentType)[0]));

        return match ($contentType) {
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt',
            default => null,
        };
    }
    public function downloadFileContent(?string $driveId, ?string $itemId, ?string $path): string
    {
        $driveId = $driveId ?: config('services.sharepoint.drive_id');

        if (!$driveId) {
            throw new \RuntimeException('SharePoint drive ID is missing.');
        }

        if ($itemId) {
            $endpoint = "https://graph.microsoft.com/v1.0/drives/{$driveId}/items/{$itemId}/content";
            $selectedMethod = 'item_id';
        } elseif ($path) {
            $encodedPath = collect(explode('/', $path))
                ->map(fn ($part) => rawurlencode($part))
                ->implode('/');

            $endpoint = "https://graph.microsoft.com/v1.0/drives/{$driveId}/root:/${encodedPath}:/content";
            $selectedMethod = 'path';
        } else {
            throw new \RuntimeException('Secure PDF SharePoint metadata is missing.');
        }

        $res = Http::timeout(300)
            ->withToken($this->token())
            ->withOptions(['allow_redirects' => ['max' => 10]])
            ->get($endpoint);

        if (app()->environment('local')) {
            \Log::info('Secure PDF Graph download', [
                'drive_id' => $driveId,
                'sharepoint_item_id' => $itemId,
                'sharepoint_path' => $path,
                'selected_download_method' => $selectedMethod,
                'graph_endpoint' => $endpoint,
                'status' => $res->status(),
                'content_type' => $res->header('Content-Type'),
            ]);
        }

        if (!$res->successful()) {
            throw new \RuntimeException('Unable to fetch file from SharePoint: ' . $res->status() . ' ' . $res->body());
        }

        return $res->body();
    }

    private function configValue(array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = config($key);
            if (!blank($value)) {
                return $value;
            }
        }

        return null;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Str;

class SecureLessonDocumentService
{
    /**
     * Resolve a lesson file path without exposing the backing storage path.
     *
     * Lesson paths have historically been stored both relative to
     * storage/app/public and relative to storage/app. Keep the resolution in
     * one place so the secure viewer does not depend on one deployment layout.
     */
    public function resolvePath(?string $filePath): ?string
    {
        $filePath = trim((string) $filePath);

        if ($filePath === '' || Str::startsWith(Str::lower($filePath), ['http://', 'https://'])) {
            return null;
        }

        $normalisedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
        $candidates = [];

        if ($this->isAbsolutePath($normalisedPath)) {
            $candidates[] = $normalisedPath;
        } else {
            $withoutPublicPrefix = preg_replace(
                '/^public' . preg_quote(DIRECTORY_SEPARATOR, '/') . '+/i',
                '',
                ltrim($normalisedPath, DIRECTORY_SEPARATOR)
            );

            foreach ($this->storageRoots() as $root) {
                $root = rtrim($root, DIRECTORY_SEPARATOR);
                $candidates[] = $root . DIRECTORY_SEPARATOR . ltrim($normalisedPath, DIRECTORY_SEPARATOR);
                $candidates[] = $root . DIRECTORY_SEPARATOR . ltrim($withoutPublicPrefix, DIRECTORY_SEPARATOR);

                if (basename($root) !== 'public') {
                    $candidates[] = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR
                        . ltrim($withoutPublicPrefix, DIRECTORY_SEPARATOR);
                }
            }
        }

        foreach (array_unique($candidates) as $candidate) {
            $realPath = realpath($candidate);

            if (!$realPath || !is_file($realPath) || !$this->isInsideAllowedRoot($realPath)) {
                continue;
            }

            return $realPath;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function storageRoots(): array
    {
        $crmRoot = config('services.crm.storage_root');

        return array_values(array_unique(array_filter([
            $crmRoot,
            $crmRoot ? rtrim($crmRoot, '/\\') . DIRECTORY_SEPARATOR . 'public' : null,
            storage_path('app/public'),
            storage_path('app/private'),
        ])));
    }

    private function isAbsolutePath(string $path): bool
    {
        return (bool) preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{1,2})/', $path);
    }

    private function isInsideAllowedRoot(string $path): bool
    {
        $path = $this->normaliseForComparison($path);

        foreach ($this->storageRoots() as $root) {
            $realRoot = realpath($root);

            if (!$realRoot) {
                continue;
            }

            $realRoot = rtrim($this->normaliseForComparison($realRoot), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR;

            if (str_starts_with($path . DIRECTORY_SEPARATOR, $realRoot)) {
                return true;
            }
        }

        return false;
    }

    private function normaliseForComparison(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        return DIRECTORY_SEPARATOR === '\\' ? strtolower($path) : $path;
    }
}

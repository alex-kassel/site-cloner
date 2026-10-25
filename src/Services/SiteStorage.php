<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Services;

use AlexKassel\SiteCloner\Data\SiteManifest;
use Illuminate\Filesystem\Filesystem;

final class SiteStorage
{
    private Filesystem $filesystem;

    private string $basePath;

    public function __construct(?string $basePath = null, ?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem;
        $this->basePath = $basePath ?? (string) config('site-cloner.storage_path', storage_path('app/site-cloner/sites'));
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getSitePath(string $siteId): string
    {
        return rtrim($this->basePath, '/').'/'.$siteId;
    }

    public function getManifestPath(string $siteId): string
    {
        return $this->getSitePath($siteId).'/manifest.json';
    }

    public function exists(string $siteId): bool
    {
        return $this->filesystem->exists($this->getManifestPath($siteId));
    }

    public function getManifest(string $siteId): ?SiteManifest
    {
        $path = $this->getManifestPath($siteId);
        if (! $this->filesystem->exists($path)) {
            return null;
        }

        try {
            $contents = $this->filesystem->get($path);
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                return null;
            }

            return SiteManifest::fromArray($decoded);
        } catch (\Throwable) {
            return null;
        }
    }

    public function saveManifest(SiteManifest $manifest): void
    {
        $siteDir = $this->getSitePath($manifest->id);
        if (! $this->filesystem->isDirectory($siteDir)) {
            $this->filesystem->makeDirectory($siteDir, 0755, true);
        }

        $json = json_encode($manifest->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->filesystem->put($this->getManifestPath($manifest->id), (string) $json);
    }

    /**
     * @return array<int, SiteManifest>
     */
    public function allSites(): array
    {
        if (! $this->filesystem->isDirectory($this->basePath)) {
            return [];
        }

        $directories = $this->filesystem->directories($this->basePath);
        $sites = [];

        foreach ($directories as $dir) {
            $siteId = basename($dir);
            $manifest = $this->getManifest($siteId);
            if ($manifest !== null) {
                $sites[] = $manifest;
            }
        }

        // Sort latest started/completed first
        usort($sites, static function (SiteManifest $a, SiteManifest $b): int {
            return strcmp($b->startedAt ?? '', $a->startedAt ?? '');
        });

        return $sites;
    }

    public function deleteSite(string $siteId): bool
    {
        $siteDir = $this->getSitePath($siteId);
        if ($this->filesystem->isDirectory($siteDir)) {
            return $this->filesystem->deleteDirectory($siteDir);
        }

        return false;
    }

    public function saveFile(string $siteId, string $relativePath, string $content): int
    {
        $relativePath = ltrim($relativePath, '/');
        $fullPath = $this->getSitePath($siteId).'/'.$relativePath;
        $dir = dirname($fullPath);

        if (! $this->filesystem->isDirectory($dir)) {
            $this->filesystem->makeDirectory($dir, 0755, true);
        }

        $this->filesystem->put($fullPath, $content);

        return strlen($content);
    }

    public function hasFile(string $siteId, string $relativePath): bool
    {
        $fullPath = $this->getSitePath($siteId).'/'.ltrim($relativePath, '/');

        return $this->filesystem->exists($fullPath);
    }

    public function getFile(string $siteId, string $relativePath): ?string
    {
        $fullPath = $this->getSitePath($siteId).'/'.ltrim($relativePath, '/');
        if (! $this->filesystem->exists($fullPath)) {
            return null;
        }

        return $this->filesystem->get($fullPath);
    }

    public function getFilePath(string $siteId, string $relativePath): string
    {
        return $this->getSitePath($siteId).'/'.ltrim($relativePath, '/');
    }

    public function createZip(string $siteId): ?string
    {
        $siteDir = $this->getSitePath($siteId);
        if (! $this->filesystem->isDirectory($siteDir)) {
            return null;
        }

        $exportDir = rtrim($this->basePath, '/').'/_exports';
        if (! $this->filesystem->isDirectory($exportDir)) {
            $this->filesystem->makeDirectory($exportDir, 0755, true);
        }

        $zipPath = $exportDir.'/'.$siteId.'.zip';
        if ($this->filesystem->exists($zipPath)) {
            $this->filesystem->delete($zipPath);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $files = $this->filesystem->allFiles($siteDir);
        foreach ($files as $file) {
            $relativePath = substr($file->getPathname(), strlen($siteDir) + 1);
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $zip->close();

        return $zipPath;
    }
}

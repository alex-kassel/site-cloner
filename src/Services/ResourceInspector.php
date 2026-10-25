<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Services;

use Illuminate\Filesystem\Filesystem;

final class ResourceInspector
{
    private Filesystem $filesystem;

    public function __construct(
        private SiteStorage $storage,
        ?Filesystem $filesystem = null,
    ) {
        $this->filesystem = $filesystem ?? new Filesystem;
    }

    /**
     * Inspect a site and return categorized external resource usage.
     *
     * @return array{
     *     summary: array{
     *         total_domains: int,
     *         downloaded_count: int,
     *         external_count: int,
     *         deactivated_count: int,
     *     },
     *     domains: array<string, array{
     *         domain: string,
     *         status: string,
     *         status_label: string,
     *         count: int,
     *         types: array<string, int>,
     *         urls: array<int, string>,
     *     }>
     * }
     */
    public function inspect(string $siteId, bool $forceRefresh = false): array
    {
        $manifest = $this->storage->getManifest($siteId);
        if ($manifest !== null && ! empty($manifest->externalResources) && ! $forceRefresh) {
            /** @var array{summary: array{total_domains: int, downloaded_count: int, external_count: int, deactivated_count: int}, domains: array<string, array{domain: string, status: string, status_label: string, count: int, types: array<string, int>, urls: array<int, string>}>} $cached */
            $cached = $manifest->externalResources;

            return $cached;
        }

        $sitePath = $this->storage->getSitePath($siteId);
        if (! $this->filesystem->isDirectory($sitePath)) {
            return [
                'summary' => [
                    'total_domains' => 0,
                    'downloaded_count' => 0,
                    'external_count' => 0,
                    'deactivated_count' => 0,
                ],
                'domains' => [],
            ];
        }

        // Collect all HTML files
        $htmlFiles = $this->filesystem->glob($sitePath.'/*.html') ?: [];
        $subHtmlFiles = $this->filesystem->glob($sitePath.'/*/*.html') ?: [];
        $allHtmlFiles = array_unique(array_merge($htmlFiles, $subHtmlFiles));

        // Limit scanning to first 60 HTML files for performance on huge documentation sites
        $filesToScan = array_slice($allHtmlFiles, 0, 60);

        /** @var array<string, array{domain: string, status: string, status_label: string, count: int, types: array<string, int>, urls: array<string, bool>}> $domainMap */
        $domainMap = [];

        foreach ($filesToScan as $filePath) {
            $content = $this->filesystem->get($filePath);
            $this->parseHtmlContent($content, $domainMap);
        }

        // Check if there are downloaded assets in _external directory
        $externalDir = $sitePath.'/_external';
        if ($this->filesystem->isDirectory($externalDir)) {
            $downloadedDomains = $this->filesystem->directories($externalDir);
            foreach ($downloadedDomains as $domainDir) {
                $domainName = basename($domainDir);
                $statusDownloaded = $this->trans('site-cloner::api.resource_types.downloaded_label', 'Downloaded locally (offline)');
                if (! isset($domainMap[$domainName])) {
                    $domainMap[$domainName] = [
                        'domain' => $domainName,
                        'status' => 'downloaded',
                        'status_label' => $statusDownloaded,
                        'count' => 0,
                        'types' => [],
                        'urls' => [],
                    ];
                } else {
                    $domainMap[$domainName]['status'] = 'downloaded';
                    $domainMap[$domainName]['status_label'] = $statusDownloaded;
                }

                // Count files in domain directory
                $files = $this->filesystem->allFiles($domainDir);
                foreach ($files as $file) {
                    $ext = strtolower($file->getExtension());
                    $type = $this->extensionToType($ext);
                    $domainMap[$domainName]['types'][$type] = ($domainMap[$domainName]['types'][$type] ?? 0) + 1;
                    $domainMap[$domainName]['count']++;
                    $rel = str_replace($sitePath.'/', '', $file->getPathname());
                    $domainMap[$domainName]['urls'][$rel] = true;
                }
            }
        }

        // Format result
        $downloadedCount = 0;
        $externalCount = 0;
        $deactivatedCount = 0;

        $formattedDomains = [];
        foreach ($domainMap as $domain => $data) {
            $urlsList = array_slice(array_keys($data['urls']), 0, 50);
            $count = max($data['count'], count($urlsList));

            if ($data['status'] === 'downloaded') {
                $downloadedCount += $count;
            } elseif ($data['status'] === 'external') {
                $externalCount += $count;
            } else {
                $deactivatedCount += $count;
            }

            $formattedDomains[$domain] = [
                'domain' => $data['domain'],
                'status' => $data['status'],
                'status_label' => $data['status_label'],
                'count' => $count,
                'types' => $data['types'],
                'urls' => $urlsList,
            ];
        }

        // Sort domains: downloaded first, then external, then deactivated, then by count descending
        uasort($formattedDomains, static function (array $a, array $b): int {
            $priority = ['downloaded' => 1, 'external' => 2, 'deactivated' => 3];
            $pA = $priority[$a['status']] ?? 4;
            $pB = $priority[$b['status']] ?? 4;
            if ($pA !== $pB) {
                return $pA <=> $pB;
            }

            return $b['count'] <=> $a['count'];
        });

        $result = [
            'summary' => [
                'total_domains' => count($formattedDomains),
                'downloaded_count' => $downloadedCount,
                'external_count' => $externalCount,
                'deactivated_count' => $deactivatedCount,
            ],
            'domains' => $formattedDomains,
        ];

        // Cache in manifest if exists
        if ($manifest !== null) {
            $manifest->externalResources = $result;
            $this->storage->saveManifest($manifest);
        }

        return $result;
    }

    /**
     * @param  array<string, array{domain: string, status: string, status_label: string, count: int, types: array<string, int>, urls: array<string, bool>}>  $domainMap
     */
    private function parseHtmlContent(string $html, array &$domainMap): void
    {
        // 1. Matches downloaded external assets (_external/{domain}/{path})
        if (preg_match_all('/_external\/([a-zA-Z0-9.\-]+)\/([^"\' >]+)/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $domain = strtolower($match[1]);
                $path = $match[2];
                $this->registerResource($domainMap, $domain, 'downloaded', $this->trans('site-cloner::api.resource_types.downloaded_label', 'Downloaded locally (offline)'), $path);
            }
        }

        // 2. Matches live external scripts, styles, images (https://{domain}/...)
        if (preg_match_all('/<(?:script|link|img|source|video|audio)[^>]+(?:src|href)=["\'](https?:\/\/([a-zA-Z0-9.\-]+)[^"\']*)["\']/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fullUrl = $match[1];
                $domain = strtolower($match[2]);
                if (! str_contains($fullUrl, '/_external/')) {
                    $this->registerResource($domainMap, $domain, 'external', $this->trans('site-cloner::api.resource_types.external_label', 'External CDN (online)'), $fullUrl);
                }
            }
        }

        // 3. Matches deactivated external links (data-original-href="https://{domain}/...")
        if (preg_match_all('/data-original-href=["\'](https?:\/\/([a-zA-Z0-9.\-]+)[^"\']*)["\']/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fullUrl = $match[1];
                $domain = strtolower($match[2]);
                $this->registerResource($domainMap, $domain, 'deactivated', $this->trans('site-cloner::api.resource_types.deactivated_label', 'Uncloned section (deactivated)'), $fullUrl);
            }
        }
    }

    /**
     * @param  array<string, array{domain: string, status: string, status_label: string, count: int, types: array<string, int>, urls: array<string, bool>}>  $domainMap
     */
    private function registerResource(
        array &$domainMap,
        string $domain,
        string $status,
        string $statusLabel,
        string $urlOrPath,
    ): void {
        if (! isset($domainMap[$domain])) {
            $domainMap[$domain] = [
                'domain' => $domain,
                'status' => $status,
                'status_label' => $statusLabel,
                'count' => 0,
                'types' => [],
                'urls' => [],
            ];
        }

        if (! isset($domainMap[$domain]['urls'][$urlOrPath])) {
            $domainMap[$domain]['urls'][$urlOrPath] = true;
            $domainMap[$domain]['count']++;

            $type = $this->detectType($urlOrPath, $status);
            $domainMap[$domain]['types'][$type] = ($domainMap[$domain]['types'][$type] ?? 0) + 1;
        }
    }

    private function detectType(string $urlOrPath, string $status): string
    {
        if ($status === 'deactivated') {
            return $this->trans('site-cloner::api.resource_types.external_links', 'External Links');
        }

        $path = strtolower((string) parse_url($urlOrPath, PHP_URL_PATH));
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, ['js', 'mjs', 'cjs'], true) || str_contains($path, '.js') || str_contains($path, 'script') || str_contains($path, 'beacon')) {
            return $this->trans('site-cloner::api.resource_types.javascript', 'JavaScript');
        }

        if ($ext === 'css' || str_contains($path, '/css') || str_contains($path, 'style')) {
            return $this->trans('site-cloner::api.resource_types.css', 'CSS');
        }

        if (in_array($ext, ['woff', 'woff2', 'ttf', 'eot', 'otf'], true) || str_contains($path, 'font')) {
            return $this->trans('site-cloner::api.resource_types.fonts', 'Fonts');
        }

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'ico', 'avif'], true) || str_contains($path, 'image') || str_contains($path, 'avatar')) {
            return $this->trans('site-cloner::api.resource_types.images', 'Images');
        }

        if (in_array($ext, ['mp4', 'webm', 'ogg', 'mp3', 'wav'], true)) {
            return $this->trans('site-cloner::api.resource_types.media', 'Media');
        }

        return $this->trans('site-cloner::api.resource_types.other', 'Other');
    }

    private function extensionToType(string $ext): string
    {
        return match ($ext) {
            'js', 'mjs', 'cjs' => $this->trans('site-cloner::api.resource_types.javascript', 'JavaScript'),
            'css' => $this->trans('site-cloner::api.resource_types.css', 'CSS'),
            'woff', 'woff2', 'ttf', 'eot', 'otf' => $this->trans('site-cloner::api.resource_types.fonts', 'Fonts'),
            'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'ico', 'avif' => $this->trans('site-cloner::api.resource_types.images', 'Images'),
            'mp4', 'webm', 'ogg', 'mp3', 'wav' => $this->trans('site-cloner::api.resource_types.media', 'Media'),
            default => $this->trans('site-cloner::api.resource_types.other', 'Other'),
        };
    }

    private function trans(string $key, string $default): string
    {
        if (function_exists('app') && app()->bound('translator')) {
            $translated = __($key);

            return is_string($translated) ? $translated : $default;
        }

        return $default;
    }
}

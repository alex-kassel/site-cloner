<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Services;

final class UrlNormalizer
{
    /**
     * Normalize URL by stripping fragments and query parameters.
     */
    public function normalize(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $parsed = parse_url($url);
        if ($parsed === false || empty($parsed['scheme']) || empty($parsed['host'])) {
            return $url;
        }

        $scheme = strtolower($parsed['scheme']);
        $host = strtolower($parsed['host']);
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $path = $parsed['path'] ?? '/';

        return "{$scheme}://{$host}{$port}{$path}";
    }

    /**
     * Resolve a potentially relative or scheme-relative URL against a base URL.
     */
    public function resolve(string $relativeUrl, string $baseUrl): string
    {
        $relativeUrl = trim($relativeUrl);

        if ($relativeUrl === '' || str_starts_with($relativeUrl, '#') || str_starts_with($relativeUrl, 'javascript:') || str_starts_with($relativeUrl, 'data:') || str_starts_with($relativeUrl, 'mailto:') || str_starts_with($relativeUrl, 'tel:')) {
            return $relativeUrl;
        }

        // Scheme-relative: e.g. //cdn.jsdelivr.net/...
        if (str_starts_with($relativeUrl, '//')) {
            $baseScheme = parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https';

            return $baseScheme.':'.$relativeUrl;
        }

        // Already absolute
        if (preg_match('#^https?://#i', $relativeUrl) === 1) {
            return $relativeUrl;
        }

        $parsedBase = parse_url($baseUrl);
        if ($parsedBase === false || empty($parsedBase['host'])) {
            return $relativeUrl;
        }

        $scheme = $parsedBase['scheme'] ?? 'https';
        $host = $parsedBase['host'];
        $port = isset($parsedBase['port']) ? ':'.$parsedBase['port'] : '';
        $baseRoot = "{$scheme}://{$host}{$port}";

        // Root-relative path: e.g. /assets/css/style.css
        if (str_starts_with($relativeUrl, '/')) {
            return $baseRoot.$relativeUrl;
        }

        // Relative path: resolve against directory of baseUrl
        $basePath = $parsedBase['path'] ?? '/';
        $baseDir = str_ends_with($basePath, '/') ? $basePath : dirname($basePath).'/';
        if ($baseDir === '\\' || $baseDir === '.') {
            $baseDir = '/';
        }

        $combinedPath = $baseDir.$relativeUrl;

        // Resolve '.' and '..' segments
        $segments = explode('/', $combinedPath);
        $resolved = [];
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($resolved);
            } else {
                $resolved[] = $segment;
            }
        }

        return $baseRoot.'/'.implode('/', $resolved);
    }

    /**
     * Extract normalized base scope directory for a given target URL.
     * e.g. "https://example.com/demo/test/index.html" -> "/demo/test/"
     */
    public function getScopePath(string $url): string
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';

        if ($path === '' || $path === '/') {
            return '/';
        }

        // If path looks like a file with an extension, take its directory
        $lastSegment = basename($path);
        if (str_contains($lastSegment, '.')) {
            $dir = dirname($path);

            return rtrim($dir, '/').'/';
        }

        return rtrim($path, '/').'/';
    }

    /**
     * Check if a target URL is strictly within the allowed scope path.
     */
    public function isInScope(string $url, string $rootScopeUrl): bool
    {
        $parsedTarget = parse_url($this->normalize($url));
        $parsedRoot = parse_url($this->normalize($rootScopeUrl));

        if ($parsedTarget === false || $parsedRoot === false) {
            return false;
        }

        $targetHost = strtolower($parsedTarget['host'] ?? '');
        $rootHost = strtolower($parsedRoot['host'] ?? '');

        if ($targetHost !== $rootHost) {
            return false;
        }

        $targetPath = $parsedTarget['path'] ?? '/';
        $scopePath = $this->getScopePath($rootScopeUrl);

        if ($scopePath === '/') {
            return true;
        }

        return str_starts_with($targetPath, $scopePath);
    }

    /**
     * Check if a URL host belongs to an external CDN list.
     *
     * @param  array<int, string>  $cdnHosts
     */
    public function isExternalCdn(string $url, array $cdnHosts): bool
    {
        $parsedHost = parse_url($url, PHP_URL_HOST);
        if (! is_string($parsedHost) || $parsedHost === '') {
            return false;
        }

        $host = strtolower($parsedHost);

        foreach ($cdnHosts as $cdn) {
            $cdn = strtolower($cdn);
            if ($host === $cdn || str_ends_with($host, '.'.$cdn)) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Services;

final class SiteSlugGenerator
{
    /**
     * Generate slug according to rule: subdomain-domain-extension-path.
     * All lower-cased, non-alphanumeric replaced with hyphens, multiple hyphens trimmed.
     */
    public function generate(string $url): string
    {
        $parsed = parse_url(trim($url));
        if ($parsed === false || empty($parsed['host'])) {
            $cleaned = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower(trim($url))) ?? '';

            return trim($cleaned, '-');
        }

        $host = strtolower($parsed['host']);
        // Replace dots and colons (ports) with hyphens
        $hostSlug = preg_replace('/[^a-z0-9]+/', '-', $host) ?? '';
        $hostSlug = trim($hostSlug, '-');

        $path = $parsed['path'] ?? '';
        $path = trim($path, '/');

        if ($path === '') {
            return $hostSlug;
        }

        // Convert path parts: e.g. "content/demo/AppDashboard" -> "content-demo-appdashboard"
        $pathSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower($path)) ?? '';
        $pathSlug = trim($pathSlug, '-');

        if ($pathSlug === '') {
            return $hostSlug;
        }

        return $hostSlug.'-'.$pathSlug;
    }
}

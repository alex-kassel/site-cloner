<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Services;

final class AssetRewriter
{
    /**
     * @param  array<int, string>  $externalCdnHosts
     */
    public function __construct(
        private UrlNormalizer $urlNormalizer,
        private array $externalCdnHosts = [],
    ) {}

    /**
     * Determine local relative storage path for any asset URL.
     */
    public function toLocalAssetPath(string $assetUrl, string $rootHost): string
    {
        $normalized = $this->urlNormalizer->normalize($assetUrl);
        $parsed = parse_url($normalized);
        $host = strtolower($parsed['host'] ?? $rootHost);
        $path = $parsed['path'] ?? '/asset.dat';

        // Strip leading slash
        $trimmedPath = ltrim($path, '/');
        if ($trimmedPath === '') {
            $trimmedPath = 'index.html';
        }

        // If from another host, namespace it under _external/{host}/...
        if ($host !== strtolower($rootHost)) {
            $safeHost = preg_replace('/[^a-z0-9\.\-]+/', '_', $host) ?? 'external';

            return '_external/'.$safeHost.'/'.$trimmedPath;
        }

        return $trimmedPath;
    }

    /**
     * Determine local relative path for an in-scope HTML page URL.
     */
    public function toLocalPagePath(string $pageUrl, string $rootScopeUrl): string
    {
        $scopePath = $this->urlNormalizer->getScopePath($rootScopeUrl);
        $normalized = $this->urlNormalizer->normalize($pageUrl);
        $parsed = parse_url($normalized);
        $path = $parsed['path'] ?? '/';

        $trimmedScope = rtrim($scopePath, '/');
        $trimmedPath = rtrim($path, '/');

        // If the path matches the root scope itself, always name it index.html
        if ($trimmedPath === $trimmedScope) {
            return 'index.html';
        }

        if ($scopePath !== '/' && str_starts_with($trimmedPath.'/', $scopePath)) {
            $relative = substr($trimmedPath.'/', strlen($scopePath));
        } else {
            $relative = ltrim($path, '/');
        }

        $relative = trim($relative, '/');
        if ($relative === '') {
            return 'index.html';
        }

        if (str_ends_with($relative, '/')) {
            $relative .= 'index.html';
        } elseif (! str_contains(basename($relative), '.')) {
            $relative .= '.html';
        }

        return $relative;
    }

    /**
     * Compute relative path from one file within the site to another.
     */
    public function getRelativePath(string $fromPath, string $toPath): string
    {
        $fromPath = trim(str_replace('\\', '/', $fromPath), '/');
        $toPath = trim(str_replace('\\', '/', $toPath), '/');

        if ($fromPath === $toPath) {
            return basename($toPath);
        }

        $fromDir = dirname($fromPath);
        if ($fromDir === '.' || $fromDir === '') {
            return $toPath;
        }

        $fromParts = explode('/', $fromDir);
        $toParts = explode('/', $toPath);

        while (count($fromParts) > 0 && count($toParts) > 0 && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $prefix = str_repeat('../', count($fromParts));

        return $prefix.implode('/', $toParts);
    }

    /**
     * Rewrite HTML document:
     * - Discovers and rewrites all assets (<link>, <script>, <img>, <svg><use>, audio, video, source).
     * - Discovers and rewrites in-scope <a> links.
     * - Deactivates out-of-scope <a> links.
     * - Leaves external CDN hosts untouched.
     *
     * @param  (callable(string $assetUrl, string $localPath): void)|null  $onAssetFound
     * @param  (callable(string $pageUrl, string $localPagePath): void)|null  $onPageFound
     */
    public function rewriteHtml(
        string $html,
        string $currentUrl,
        string $rootScopeUrl,
        string $siteId,
        ?callable $onAssetFound = null,
        ?callable $onPageFound = null,
        string $downloadMode = 'light',
        ?string $currentPageLocalPath = null,
    ): string {
        $onAssetFound ??= static function (): void {};
        $onPageFound ??= static function (): void {};
        if (trim($html) === '') {
            return $html;
        }

        $rootHost = (string) parse_url($rootScopeUrl, PHP_URL_HOST);
        $currentPageLocalPath ??= $this->toLocalPagePath($currentUrl, $rootScopeUrl);

        // Use DomCrawler / DOMDocument for robust traversal
        $dom = new \DOMDocument;
        // Suppress HTML5 parsing warnings
        $internalErrors = libxml_use_internal_errors(true);
        $encodedHtml = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
        @$dom->loadHTML($encodedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors($internalErrors);

        $xpath = new \DOMXPath($dom);

        // Remove any <base> tag so relative paths resolve cleanly against the document location
        $baseTags = $xpath->query('//base');
        if ($baseTags !== false) {
            /** @var \DOMElement $baseTag */
            foreach ($baseTags as $baseTag) {
                $baseTag->parentNode?->removeChild($baseTag);
            }
        }

        // 1. Process Stylesheets & Preloads (<link>)
        $links = $xpath->query('//link[@href]');
        if ($links !== false) {
            /** @var \DOMElement $link */
            foreach ($links as $link) {
                $rawHref = $link->getAttribute('href');
                $rel = strtolower($link->getAttribute('rel'));
                $resolved = $this->urlNormalizer->resolve($rawHref, $currentUrl);

                if (! $this->shouldDownloadAsset($resolved, $rootHost, $downloadMode)) {
                    continue; // Leave Google fonts / CDN as is, or external CDN in light mode
                }

                if ($this->isAssetUrl($resolved) || in_array($rel, ['stylesheet', 'icon', 'shortcut icon', 'apple-touch-icon', 'preload', 'modulepreload', 'manifest'], true)) {
                    $localPath = $this->toLocalAssetPath($resolved, $rootHost);
                    $onAssetFound($resolved, $localPath);
                    $link->setAttribute('href', $this->getRelativePath($currentPageLocalPath, $localPath));
                }
            }
        }

        // 2. Process Scripts (<script src="...">)
        $scripts = $xpath->query('//script[@src]');
        if ($scripts !== false) {
            /** @var \DOMElement $script */
            foreach ($scripts as $script) {
                $rawSrc = $script->getAttribute('src');
                $resolved = $this->urlNormalizer->resolve($rawSrc, $currentUrl);

                if (! $this->shouldDownloadAsset($resolved, $rootHost, $downloadMode)) {
                    continue;
                }

                $localPath = $this->toLocalAssetPath($resolved, $rootHost);
                $onAssetFound($resolved, $localPath);
                $script->setAttribute('src', $this->getRelativePath($currentPageLocalPath, $localPath));
            }
        }

        // 3. Process Images, Media, Sources, Embeds, iframes
        $mediaQueries = [
            '//img[@src]' => 'src',
            '//source[@src]' => 'src',
            '//video[@src]' => 'video',
            '//audio[@src]' => 'src',
            '//track[@src]' => 'src',
            '//embed[@src]' => 'src',
            '//input[@src]' => 'src',
        ];

        foreach ($mediaQueries as $query => $attr) {
            $elements = $xpath->query($query);
            if ($elements !== false) {
                /** @var \DOMElement $element */
                foreach ($elements as $element) {
                    $raw = $element->getAttribute($attr);
                    $resolved = $this->urlNormalizer->resolve($raw, $currentUrl);

                    if (! $this->shouldDownloadAsset($resolved, $rootHost, $downloadMode)) {
                        continue;
                    }

                    $localPath = $this->toLocalAssetPath($resolved, $rootHost);
                    $onAssetFound($resolved, $localPath);
                    $element->setAttribute($attr, $this->getRelativePath($currentPageLocalPath, $localPath));
                }
            }
        }

        // Handle srcset on <img> and <source>
        $srcsetElements = $xpath->query('//*[@srcset]');
        if ($srcsetElements !== false) {
            /** @var \DOMElement $element */
            foreach ($srcsetElements as $element) {
                $rawSrcset = $element->getAttribute('srcset');
                $parts = explode(',', $rawSrcset);
                $rewrittenParts = [];

                foreach ($parts as $part) {
                    $part = trim($part);
                    if ($part === '') {
                        continue;
                    }
                    $tokens = preg_split('/\s+/', $part);
                    $src = $tokens[0] ?? '';
                    $descriptor = isset($tokens[1]) ? ' '.$tokens[1] : '';

                    if ($src !== '') {
                        $resolved = $this->urlNormalizer->resolve($src, $currentUrl);
                        if ($this->shouldDownloadAsset($resolved, $rootHost, $downloadMode)) {
                            $localPath = $this->toLocalAssetPath($resolved, $rootHost);
                            $onAssetFound($resolved, $localPath);
                            $src = $this->getRelativePath($currentPageLocalPath, $localPath);
                        }
                    }
                    $rewrittenParts[] = $src.$descriptor;
                }

                $element->setAttribute('srcset', implode(', ', $rewrittenParts));
            }
        }

        // 4. Process SVG <use href="..." / xlink:href="...">
        $svgUses = $xpath->query('//*[local-name()="use"]');
        if ($svgUses !== false) {
            /** @var \DOMElement $use */
            foreach ($svgUses as $use) {
                $hrefAttr = $use->hasAttribute('href') ? 'href' : ($use->hasAttribute('xlink:href') ? 'xlink:href' : null);
                if ($hrefAttr !== null) {
                    $raw = $use->getAttribute($hrefAttr);
                    if ($raw !== '' && ! str_starts_with($raw, '#')) {
                        // e.g. "assets/icons.svg#arrow"
                        $hashPos = strpos($raw, '#');
                        $frag = $hashPos !== false ? substr($raw, $hashPos) : '';
                        $cleanUrl = $hashPos !== false ? substr($raw, 0, $hashPos) : $raw;

                        $resolved = $this->urlNormalizer->resolve($cleanUrl, $currentUrl);
                        if ($this->shouldDownloadAsset($resolved, $rootHost, $downloadMode)) {
                            $localPath = $this->toLocalAssetPath($resolved, $rootHost);
                            $onAssetFound($resolved, $localPath);
                            $use->setAttribute($hrefAttr, $this->getRelativePath($currentPageLocalPath, $localPath).$frag);
                        }
                    }
                }
            }
        }

        // 5. Process Anchor Links (<a href="...">)
        $anchors = $xpath->query('//a[@href]');
        if ($anchors !== false) {
            /** @var \DOMElement $anchor */
            foreach ($anchors as $anchor) {
                $rawHref = $anchor->getAttribute('href');
                $trimmed = trim($rawHref);

                if ($trimmed === '' || str_starts_with($trimmed, '#') || str_starts_with($trimmed, 'javascript:') || str_starts_with($trimmed, 'mailto:') || str_starts_with($trimmed, 'tel:')) {
                    continue;
                }

                $resolved = $this->urlNormalizer->resolve($trimmed, $currentUrl);
                $normalized = $this->urlNormalizer->normalize($resolved);
                $targetHost = strtolower((string) parse_url($normalized, PHP_URL_HOST));

                // 1. External Links (different domain / external site, e.g. GitHub, docs, partners):
                // Always keep working as real external links so user can navigate if online
                if ($targetHost !== '' && strcasecmp($targetHost, $rootHost) !== 0) {
                    $anchor->setAttribute('href', $resolved);
                    if (! $anchor->hasAttribute('target')) {
                        $anchor->setAttribute('target', '_blank');
                    }
                    if (! $anchor->hasAttribute('rel')) {
                        $anchor->setAttribute('rel', 'noopener noreferrer');
                    }

                    continue;
                }

                // 2. Same-domain asset links (e.g. /files/manual.pdf)
                if ($this->isAssetUrl($normalized)) {
                    $localPath = $this->toLocalAssetPath($normalized, $rootHost);
                    $onAssetFound($normalized, $localPath);
                    $anchor->setAttribute('href', $this->getRelativePath($currentPageLocalPath, $localPath));

                    continue;
                }

                // 3. Same-domain page links:
                if ($this->urlNormalizer->isInScope($normalized, $rootScopeUrl)) {
                    // In-scope: link to local downloaded relative page
                    $localPagePath = $this->toLocalPagePath($normalized, $rootScopeUrl);
                    $onPageFound($normalized, $localPagePath);
                    $anchor->setAttribute('href', $this->getRelativePath($currentPageLocalPath, $localPagePath));
                } else {
                    // Out-of-scope on same site (neighboring folder that was not cloned, e.g. /partners when downloading /docs):
                    // Deactivate so it does not result in a broken local file link
                    $anchor->setAttribute('href', 'javascript:void(0)');
                    $anchor->setAttribute('data-site-cloner-disabled', 'true');
                    $anchor->setAttribute('data-original-href', $resolved);
                    $anchor->setAttribute('title', 'Раздел не скачан (вне диапазона): '.$resolved);
                    $anchor->setAttribute('onclick', 'window.__siteClonerShowNotice && window.__siteClonerShowNotice(this, event)');
                }
            }
        }

        // 6. Process inline styles with url(...)
        $styledElements = $xpath->query('//*[@style]');
        if ($styledElements !== false) {
            /** @var \DOMElement $el */
            foreach ($styledElements as $el) {
                $style = $el->getAttribute('style');
                if (str_contains($style, 'url(')) {
                    $rewrittenStyle = $this->rewriteCssUrls($style, $currentUrl, $rootHost, $currentPageLocalPath, $onAssetFound, $downloadMode);
                    $el->setAttribute('style', $rewrittenStyle);
                }
            }
        }

        // Process <style> tags
        $styleElements = $xpath->query('//style');
        if ($styleElements !== false) {
            /** @var \DOMElement $styleEl */
            foreach ($styleElements as $styleEl) {
                $content = $styleEl->nodeValue ?? '';
                if (str_contains($content, 'url(')) {
                    $styleEl->nodeValue = $this->rewriteCssUrls($content, $currentUrl, $rootHost, $currentPageLocalPath, $onAssetFound, $downloadMode);
                }
            }
        }

        // 7. Inject Viewer Notice Script into <body>
        $scriptTag = $dom->createElement('script');
        $noticeHeading = addslashes($this->trans('site-cloner::common.viewer_notice.heading', 'Section not downloaded:'));
        $noticeBody = addslashes($this->trans('site-cloner::common.viewer_notice.body', 'This link points to an uncloned section outside the downloaded folder scope:'));

        $scriptTag->nodeValue = <<<JS
window.__siteClonerShowNotice = function(el, ev) {
    if (ev) ev.preventDefault();
    var href = el.getAttribute('data-original-href') || 'external link';
    var toast = document.getElementById('__site_cloner_toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = '__site_cloner_toast';
        toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1f2937;color:#fff;padding:12px 20px;border-radius:10px;font-family:sans-serif;font-size:13px;box-shadow:0 10px 25px rgba(0,0,0,0.3);z-index:999999;transition:opacity 0.3s;max-width:90%;text-align:center;border:1px solid #374151;';
        document.body.appendChild(toast);
    }
    toast.innerHTML = '📁 <strong>{$noticeHeading}</strong> {$noticeBody}<br><span style="color:#93c5fd;word-break:break-all;">' + href + '</span>';
    toast.style.opacity = '1';
    toast.style.display = 'block';
    clearTimeout(window.__toastTimeout);
    window.__toastTimeout = setTimeout(function() {
        toast.style.opacity = '0';
        setTimeout(function() { toast.style.display = 'none'; }, 300);
    }, 4000);
};
JS;
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body !== null) {
            $body->appendChild($scriptTag);
        }

        $savedHtml = $dom->saveHTML();

        return $savedHtml !== false ? $savedHtml : $html;
    }

    /**
     * Rewrite CSS file content (handles url(...) and @import).
     *
     * @param  (callable(string $assetUrl, string $localPath): void)|null  $onAssetFound
     */
    public function rewriteCss(
        string $css,
        string $currentCssUrl,
        string $rootHost,
        string $siteId,
        ?callable $onAssetFound = null,
        string $downloadMode = 'light',
        ?string $currentCssLocalPath = null,
    ): string {
        $onAssetFound ??= static function (): void {};
        $currentCssLocalPath ??= $this->toLocalAssetPath($currentCssUrl, $rootHost);

        return $this->rewriteCssUrls($css, $currentCssUrl, $rootHost, $currentCssLocalPath, $onAssetFound, $downloadMode);
    }

    /**
     * @param  (callable(string $assetUrl, string $localPath): void)|null  $onAssetFound
     */
    private function rewriteCssUrls(
        string $css,
        string $currentUrl,
        string $rootHost,
        string $sourceLocalPath,
        ?callable $onAssetFound = null,
        string $downloadMode = 'light',
    ): string {
        $onAssetFound ??= static function (): void {};

        // Regex matches url(...) with or without quotes
        return (string) preg_replace_callback(
            '/url\(\s*(?:\'([^\']+)\'|"([^"]+)"|([^)]+))\s*\)/i',
            function (array $matches) use ($currentUrl, $rootHost, $sourceLocalPath, $onAssetFound, $downloadMode): string {
                $raw = '';
                if (! empty($matches[1])) {
                    $raw = $matches[1];
                } elseif (! empty($matches[2])) {
                    $raw = $matches[2];
                } elseif (! empty($matches[3])) {
                    $raw = $matches[3];
                }
                $raw = trim($raw);

                if ($raw === '' || str_starts_with($raw, 'data:') || str_starts_with($raw, '#')) {
                    return $matches[0];
                }

                // Strip hash fragment if present for downloading, but keep it in url if needed
                $hashPos = strpos($raw, '#');
                $frag = $hashPos !== false ? substr($raw, $hashPos) : '';
                $cleanRaw = $hashPos !== false ? substr($raw, 0, $hashPos) : $raw;

                $resolved = $this->urlNormalizer->resolve($cleanRaw, $currentUrl);

                if (! $this->shouldDownloadAsset($resolved, $rootHost, $downloadMode)) {
                    return 'url("'.$resolved.$frag.'")';
                }

                $localPath = $this->toLocalAssetPath($resolved, $rootHost);
                $onAssetFound($resolved, $localPath);

                return 'url("'.$this->getRelativePath($sourceLocalPath, $localPath).$frag.'")';
            },
            $css
        );
    }

    private function shouldDownloadAsset(string $url, string $rootHost, string $downloadMode): bool
    {
        if ($this->urlNormalizer->isExternalCdn($url, $this->externalCdnHosts)) {
            return false;
        }

        if ($downloadMode === 'light') {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host !== null && $host !== '' && strcasecmp((string) $host, $rootHost) !== 0) {
                return false;
            }
        }

        return true;
    }

    private function isAssetUrl(string $url): bool
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        $assetExtensions = [
            'css', 'js', 'json', 'xml',
            'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'bmp', 'ico', 'tiff', 'avif',
            'woff', 'woff2', 'ttf', 'eot', 'otf',
            'mp4', 'webm', 'ogg', 'mp3', 'wav', 'flac',
            'pdf', 'zip', 'tar', 'gz', 'map',
        ];

        return in_array($ext, $assetExtensions, true);
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

<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Tests\Unit;

use AlexKassel\SiteCloner\Services\AssetRewriter;
use AlexKassel\SiteCloner\Services\UrlNormalizer;
use PHPUnit\Framework\TestCase;

final class AssetRewriterTest extends TestCase
{
    private AssetRewriter $rewriter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rewriter = new AssetRewriter(
            urlNormalizer: new UrlNormalizer,
            externalCdnHosts: ['fonts.googleapis.com'],
            routePrefix: 'site-cloner'
        );
    }

    public function test_it_rewrites_html_assets_and_in_scope_links(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans">
    <script src="/common/js/app.js"></script>
</head>
<body>
    <img src="img/logo.png" alt="Logo">
    <a href="tables.html">Tables</a>
    <a href="/contacts">Outside Contact</a>
</body>
</html>
HTML;

        $discoveredAssets = [];
        $discoveredPages = [];

        $rewritten = $this->rewriter->rewriteHtml(
            html: $html,
            currentUrl: 'https://bootstrapmade.com/content/demo/AppDashboard/index.html',
            rootScopeUrl: 'https://bootstrapmade.com/content/demo/AppDashboard/',
            siteId: 'test-site',
            onAssetFound: function ($url, $local) use (&$discoveredAssets): void {
                $discoveredAssets[$url] = $local;
            },
            onPageFound: function ($url, $local) use (&$discoveredPages): void {
                $discoveredPages[$url] = $local;
            }
        );

        // Assets should point to viewer prefix
        $this->assertStringContainsString('/site-cloner/view/test-site/content/demo/assets/css/style.css', $rewritten);
        $this->assertStringContainsString('/site-cloner/view/test-site/common/js/app.js', $rewritten);
        $this->assertStringContainsString('/site-cloner/view/test-site/content/demo/AppDashboard/img/logo.png', $rewritten);

        // Google Fonts should remain untouched
        $this->assertStringContainsString('https://fonts.googleapis.com/css?family=Open+Sans', $rewritten);

        // In-scope page should be rewritten and registered
        $this->assertStringContainsString('/site-cloner/view/test-site/tables.html', $rewritten);
        $this->assertArrayHasKey('https://bootstrapmade.com/content/demo/AppDashboard/tables.html', $discoveredPages);

        // Out-of-scope link should be deactivated with data attributes
        $this->assertStringContainsString('data-site-cloner-disabled="true"', $rewritten);
        $this->assertStringContainsString('href="javascript:void(0)"', $rewritten);
    }

    public function test_it_rewrites_css_urls_for_fonts_and_images(): void
    {
        $css = <<<'CSS'
@font-face {
    font-family: 'FontAwesome';
    src: url('../fonts/fontawesome-webfont.woff2?v=4.7.0') format('woff2');
}
.hero {
    background: url('/assets/img/hero-bg.jpg');
}
CSS;

        $discoveredAssets = [];

        $rewritten = $this->rewriter->rewriteCss(
            css: $css,
            currentCssUrl: 'https://bootstrapmade.com/assets/css/style.css',
            rootHost: 'bootstrapmade.com',
            siteId: 'test-site',
            onAssetFound: function ($url, $local) use (&$discoveredAssets): void {
                $discoveredAssets[$url] = $local;
            }
        );

        $this->assertStringContainsString('/site-cloner/view/test-site/assets/fonts/fontawesome-webfont.woff2', $rewritten);
        $this->assertStringContainsString('/site-cloner/view/test-site/assets/img/hero-bg.jpg', $rewritten);
    }

    public function test_it_maps_scope_root_without_trailing_slash_to_index_html(): void
    {
        $path = $this->rewriter->toLocalPagePath('https://laravel.com/framework/docs', 'https://laravel.com/framework/docs');
        $this->assertSame('index.html', $path);

        $subPath = $this->rewriter->toLocalPagePath('https://laravel.com/framework/docs/routing', 'https://laravel.com/framework/docs');
        $this->assertSame('routing.html', $subPath);
    }

    public function test_it_rewrites_modulepreload_links(): void
    {
        $html = '<link rel="modulepreload" href="/build/assets/app-123.js">';
        $rewritten = $this->rewriter->rewriteHtml(
            html: $html,
            currentUrl: 'https://laravel.com/framework/docs',
            rootScopeUrl: 'https://laravel.com/framework/docs',
            siteId: 'laravel-site'
        );

        $this->assertStringContainsString('/site-cloner/view/laravel-site/build/assets/app-123.js', $rewritten);
    }

    public function test_light_mode_keeps_external_cdn_links_untouched(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="/assets/css/local-style.css">
</head>
<body></body>
</html>
HTML;

        $discoveredAssets = [];

        $rewritten = $this->rewriter->rewriteHtml(
            html: $html,
            currentUrl: 'https://example.com/demo/index.html',
            rootScopeUrl: 'https://example.com/demo/',
            siteId: 'test-site',
            onAssetFound: function ($url, $local) use (&$discoveredAssets): void {
                $discoveredAssets[$url] = $local;
            },
            downloadMode: 'light'
        );

        // External CDN should stay untouched
        $this->assertStringContainsString('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', $rewritten);
        $this->assertStringContainsString('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', $rewritten);
        $this->assertArrayNotHasKey('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', $discoveredAssets);

        // Local asset should still be rewritten and discovered
        $this->assertStringContainsString('/site-cloner/view/test-site/assets/css/local-style.css', $rewritten);
        $this->assertArrayHasKey('https://example.com/assets/css/local-style.css', $discoveredAssets);
    }

    public function test_full_mode_downloads_external_cdn(): void
    {
        $html = '<script src="https://cdn.jsdelivr.net/npm/bootstrap.js"></script>';
        $discoveredAssets = [];

        $rewritten = $this->rewriter->rewriteHtml(
            html: $html,
            currentUrl: 'https://example.com/demo/',
            rootScopeUrl: 'https://example.com/demo/',
            siteId: 'test-site',
            onAssetFound: function ($url, $local) use (&$discoveredAssets): void {
                $discoveredAssets[$url] = $local;
            },
            downloadMode: 'full'
        );

        $this->assertStringContainsString('/site-cloner/view/test-site/_external/cdn.jsdelivr.net/npm/bootstrap.js', $rewritten);
        $this->assertArrayHasKey('https://cdn.jsdelivr.net/npm/bootstrap.js', $discoveredAssets);
    }
}

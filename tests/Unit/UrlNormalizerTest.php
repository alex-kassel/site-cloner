<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Tests\Unit;

use AlexKassel\SiteCloner\Services\UrlNormalizer;
use PHPUnit\Framework\TestCase;

final class UrlNormalizerTest extends TestCase
{
    private UrlNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new UrlNormalizer;
    }

    public function test_it_strips_query_string_and_fragments(): void
    {
        $url = 'https://example.com/demo/index.html?v=1.2.3&ref=banner#section-2';
        $normalized = $this->normalizer->normalize($url);

        $this->assertSame('https://example.com/demo/index.html', $normalized);
    }

    public function test_it_resolves_relative_urls_with_parent_directories(): void
    {
        $baseUrl = 'https://bootstrapmade.com/content/demo/AppDashboard/index.html';

        $resolved = $this->normalizer->resolve('../assets/vendor/bootstrap/css/bootstrap.min.css', $baseUrl);
        $this->assertSame('https://bootstrapmade.com/content/demo/assets/vendor/bootstrap/css/bootstrap.min.css', $resolved);

        $rootRelative = $this->normalizer->resolve('/global/img/logo.png', $baseUrl);
        $this->assertSame('https://bootstrapmade.com/global/img/logo.png', $rootRelative);
    }

    public function test_it_correctly_checks_scope(): void
    {
        $rootScope = 'https://bootstrapmade.com/content/demo/AppDashboard/';

        // In scope
        $this->assertTrue($this->normalizer->isInScope('https://bootstrapmade.com/content/demo/AppDashboard/tables.html', $rootScope));
        $this->assertTrue($this->normalizer->isInScope('https://bootstrapmade.com/content/demo/AppDashboard/forms/elements.html', $rootScope));
        $this->assertTrue($this->normalizer->isInScope('https://bootstrapmade.com/content/demo/AppDashboard', $rootScope));

        // When root scope has no trailing slash
        $this->assertTrue($this->normalizer->isInScope('https://laravel.com/framework/docs', 'https://laravel.com/framework/docs'));
        $this->assertTrue($this->normalizer->isInScope('https://laravel.com/framework/docs/routing', 'https://laravel.com/framework/docs'));

        // Out of scope
        $this->assertFalse($this->normalizer->isInScope('https://bootstrapmade.com/content/demo/OtherDashboard/', $rootScope));
        $this->assertFalse($this->normalizer->isInScope('https://bootstrapmade.com/', $rootScope));
        $this->assertFalse($this->normalizer->isInScope('https://external-domain.com/content/demo/AppDashboard/', $rootScope));
    }

    public function test_it_detects_external_cdn_hosts(): void
    {
        $cdns = ['fonts.googleapis.com', 'fonts.gstatic.com'];

        $this->assertTrue($this->normalizer->isExternalCdn('https://fonts.googleapis.com/css2?family=Roboto', $cdns));
        $this->assertTrue($this->normalizer->isExternalCdn('https://fonts.gstatic.com/s/roboto/v30/test.woff2', $cdns));
        $this->assertFalse($this->normalizer->isExternalCdn('https://bootstrapmade.com/assets/css/style.css', $cdns));
    }
}

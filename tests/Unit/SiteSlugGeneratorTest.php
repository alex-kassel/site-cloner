<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Tests\Unit;

use AlexKassel\SiteCloner\Services\SiteSlugGenerator;
use PHPUnit\Framework\TestCase;

final class SiteSlugGeneratorTest extends TestCase
{
    private SiteSlugGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new SiteSlugGenerator;
    }

    public function test_it_generates_subdomain_domain_extension_path_slug(): void
    {
        $url = 'https://bootstrapmade.com/content/demo/AppDashboard/';
        $slug = $this->generator->generate($url);

        $this->assertSame('bootstrapmade-com-content-demo-appdashboard', $slug);
    }

    public function test_it_handles_multi_level_subdomains_and_extensions(): void
    {
        $url = 'https://demo.admin.example.co.uk/v2/panel/users/';
        $slug = $this->generator->generate($url);

        $this->assertSame('demo-admin-example-co-uk-v2-panel-users', $slug);
    }

    public function test_it_handles_root_domains(): void
    {
        $url = 'https://example.com/';
        $slug = $this->generator->generate($url);

        $this->assertSame('example-com', $slug);
    }
}

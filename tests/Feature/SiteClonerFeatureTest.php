<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Tests\Feature;

use AlexKassel\SiteCloner\Data\SiteManifest;
use AlexKassel\SiteCloner\Services\SiteStorage;
use AlexKassel\SiteCloner\Tests\TestCase;

final class SiteClonerFeatureTest extends TestCase
{
    public function test_dashboard_route_renders_successfully(): void
    {
        $response = $this->get('/site-cloner');
        $response->assertStatus(200);
        $response->assertSee('Site Cloner');
        $response->assertSee('Клонировать новый сайт');
    }

    public function test_check_endpoint_identifies_existing_sites(): void
    {
        $storage = $this->app->make(SiteStorage::class);
        $storage->saveManifest(new SiteManifest(
            id: 'bootstrapmade-com-content-demo-appdashboard',
            url: 'https://bootstrapmade.com/content/demo/AppDashboard/',
            baseUrl: 'https://bootstrapmade.com/content/demo/AppDashboard/',
            scopePath: '/content/demo/AppDashboard/',
            status: 'completed'
        ));

        $response = $this->postJson('/site-cloner/check', [
            'url' => 'https://bootstrapmade.com/content/demo/AppDashboard/',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => 'bootstrapmade-com-content-demo-appdashboard',
            'exists' => true,
        ]);
    }

    public function test_viewer_serves_html_and_assets(): void
    {
        $storage = $this->app->make(SiteStorage::class);
        $siteId = 'test-mock-site';

        $storage->saveManifest(new SiteManifest(
            id: $siteId,
            url: 'https://mock.test/demo/',
            baseUrl: 'https://mock.test/demo/',
            scopePath: '/demo/',
            status: 'completed'
        ));

        $storage->saveFile($siteId, 'index.html', '<h1>Hello Cloned Site</h1>');
        $storage->saveFile($siteId, 'assets/style.css', 'body { margin: 0; }');

        // Test root index
        $indexRes = $this->get('/site-cloner/view/'.$siteId);
        $indexRes->assertStatus(200);
        $indexRes->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('Hello Cloned Site', $indexRes->getContent() ?: '');

        // Test asset
        $cssRes = $this->get('/site-cloner/view/'.$siteId.'/assets/style.css');
        $cssRes->assertStatus(200);
        $cssRes->assertHeader('Content-Type', 'text/css; charset=UTF-8');
        $this->assertStringContainsString('margin: 0', $cssRes->getContent() ?: '');

        // Test not found
        $notFoundRes = $this->get('/site-cloner/view/'.$siteId.'/missing-page.html');
        $notFoundRes->assertStatus(404);
        $this->assertStringContainsString('Файл не найден в архиве', $notFoundRes->getContent() ?: '');
    }
}

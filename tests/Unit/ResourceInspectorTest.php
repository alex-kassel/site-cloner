<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Tests\Unit;

use AlexKassel\SiteCloner\Data\SiteManifest;
use AlexKassel\SiteCloner\Services\ResourceInspector;
use AlexKassel\SiteCloner\Services\SiteStorage;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

final class ResourceInspectorTest extends TestCase
{
    private string $tempDir;

    private Filesystem $filesystem;

    private SiteStorage $storage;

    private ResourceInspector $inspector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem;
        $this->tempDir = sys_get_temp_dir().'/site_cloner_test_'.uniqid();
        $this->filesystem->makeDirectory($this->tempDir, 0755, true);

        $this->storage = new SiteStorage($this->tempDir, $this->filesystem);
        $this->inspector = new ResourceInspector($this->storage, $this->filesystem);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->isDirectory($this->tempDir)) {
            $this->filesystem->deleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    public function test_it_inspects_and_categorizes_external_resources(): void
    {
        $siteId = 'test-site';
        $manifest = new SiteManifest(
            id: $siteId,
            url: 'https://example.com/demo/',
            baseUrl: 'https://example.com/demo/',
            scopePath: '/demo/'
        );
        $this->storage->saveManifest($manifest);

        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="/site-cloner/view/test-site/_external/cdn.jsdelivr.net/npm/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto">
    <script src="/site-cloner/view/test-site/_external/cdn.jsdelivr.net/npm/bootstrap.bundle.min.js"></script>
</head>
<body>
    <img src="https://images.unsplash.com/photo-123.jpg">
    <a href="javascript:void(0)" data-site-cloner-disabled="true" data-original-href="https://github.com/alex-kassel">GitHub</a>
</body>
</html>
HTML;

        $this->storage->saveFile($siteId, 'index.html', $html);

        $report = $this->inspector->inspect($siteId);

        $this->assertSame(4, $report['summary']['total_domains']);
        $this->assertArrayHasKey('cdn.jsdelivr.net', $report['domains']);
        $this->assertSame('downloaded', $report['domains']['cdn.jsdelivr.net']['status']);

        $this->assertArrayHasKey('fonts.googleapis.com', $report['domains']);
        $this->assertSame('external', $report['domains']['fonts.googleapis.com']['status']);

        $this->assertArrayHasKey('images.unsplash.com', $report['domains']);
        $this->assertSame('external', $report['domains']['images.unsplash.com']['status']);

        $this->assertArrayHasKey('github.com', $report['domains']);
        $this->assertSame('deactivated', $report['domains']['github.com']['status']);
    }
}

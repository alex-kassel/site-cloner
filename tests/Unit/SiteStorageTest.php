<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Tests\Unit;

use AlexKassel\SiteCloner\Data\SiteManifest;
use AlexKassel\SiteCloner\Services\SiteStorage;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

final class SiteStorageTest extends TestCase
{
    private string $tempDir;

    private SiteStorage $storage;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem;
        $this->tempDir = sys_get_temp_dir().'/site_cloner_test_'.uniqid();
        $this->filesystem->makeDirectory($this->tempDir, 0755, true);
        $this->storage = new SiteStorage($this->tempDir, $this->filesystem);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->isDirectory($this->tempDir)) {
            $this->filesystem->deleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    public function test_it_saves_and_retrieves_manifest(): void
    {
        $manifest = new SiteManifest(
            id: 'demo-site',
            url: 'https://example.com/demo/',
            baseUrl: 'https://example.com/demo/',
            scopePath: '/demo/',
            status: 'completed',
            filesCount: 15,
            totalBytes: 1048576,
            pagesCount: 2,
            pages: ['index.html', 'about.html']
        );

        $this->storage->saveManifest($manifest);

        $this->assertTrue($this->storage->exists('demo-site'));
        $retrieved = $this->storage->getManifest('demo-site');

        $this->assertNotNull($retrieved);
        $this->assertSame('demo-site', $retrieved->id);
        $this->assertSame(15, $retrieved->filesCount);
        $this->assertSame(1048576, $retrieved->totalBytes);
        $this->assertSame('1.00 MB', $retrieved->humanReadableSize());
    }

    public function test_it_saves_and_deletes_files_and_sites(): void
    {
        $this->storage->saveFile('test-site', 'css/app.css', 'body { color: red; }');
        $this->assertTrue($this->storage->hasFile('test-site', 'css/app.css'));
        $this->assertSame('body { color: red; }', $this->storage->getFile('test-site', 'css/app.css'));

        $this->storage->deleteSite('test-site');
        $this->assertFalse($this->storage->hasFile('test-site', 'css/app.css'));
    }
}

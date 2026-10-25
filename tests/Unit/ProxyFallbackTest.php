<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Tests\Unit;

use AlexKassel\SiteCloner\Data\SiteManifest;
use AlexKassel\SiteCloner\Services\AssetRewriter;
use AlexKassel\SiteCloner\Services\SiteClonerEngine;
use AlexKassel\SiteCloner\Services\SiteStorage;
use AlexKassel\SiteCloner\Services\UrlNormalizer;
use AlexKassel\SiteCloner\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

final class ProxyFallbackTest extends TestCase
{
    private string $tempDir;

    private SiteStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/site_cloner_proxy_test_'.uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->storage = new SiteStorage($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    public function test_engine_retries_with_proxy_on_403_and_activates_it(): void
    {
        $history = [];
        $historyMiddleware = Middleware::history($history);

        // First request to page returns 403, retry via proxy returns 200 HTML
        $mock = new MockHandler([
            new Response(403, ['Server' => 'cloudflare', 'cf-mitigated' => 'challenge'], 'Forbidden'),
            new Response(200, ['Content-Type' => 'text/html'], '<html><body><h1>Success via Proxy</h1></body></html>'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($historyMiddleware);

        $client = new Client(['handler' => $handlerStack]);

        $engine = new SiteClonerEngine(
            storage: $this->storage,
            urlNormalizer: new UrlNormalizer,
            assetRewriter: new AssetRewriter(new UrlNormalizer),
            maxPages: 1,
            httpClient: $client,
            proxy: 'socks5h://warp:1080',
        );

        $manifest = new SiteManifest(
            id: 'proxy-test-site',
            url: 'https://example.com/demo/',
            baseUrl: 'https://example.com/demo/',
            scopePath: '/demo/',
            status: 'pending',
        );

        $result = $engine->clone($manifest);

        $this->assertSame('completed', $result->status);
        $this->assertCount(2, $history);

        // First request had no proxy
        $firstOptions = $history[0]['options'];
        $this->assertArrayNotHasKey('proxy', $firstOptions);

        // Second request was retried with proxy
        $secondOptions = $history[1]['options'];
        $this->assertArrayHasKey('proxy', $secondOptions);
        $this->assertSame('socks5h://warp:1080', $secondOptions['proxy']);
    }

    public function test_proxy_configuration_defaults_to_null(): void
    {
        $this->assertNull(config('site-cloner.proxy'));
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = scandir($path) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $subPath = $path.'/'.$item;
            is_dir($subPath) ? $this->removeDirectory($subPath) : unlink($subPath);
        }
        rmdir($path);
    }
}

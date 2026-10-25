<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Services;

use AlexKassel\SiteCloner\Data\SiteManifest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final class SiteClonerEngine
{
    private Client $httpClient;

    public function __construct(
        private SiteStorage $storage,
        private UrlNormalizer $urlNormalizer,
        private AssetRewriter $assetRewriter,
        private ?int $maxPages = 500,
        private int $timeout = 30,
        private string $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
        ?Client $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => $this->timeout,
            'verify' => false,
            'http_errors' => false,
            'headers' => [
                'User-Agent' => $this->userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9,ru;q=0.8',
            ],
        ]);
    }

    /**
     * Clone a website specified by the manifest.
     *
     * @param  callable(SiteManifest $manifest): void|null  $onProgress
     */
    public function clone(SiteManifest $manifest, ?callable $onProgress = null): SiteManifest
    {
        $startTime = time();
        $manifest->status = 'downloading';
        $manifest->startedAt = date('Y-m-d H:i:s');
        $manifest->errorMessage = null;

        $this->storage->saveManifest($manifest);
        if ($onProgress !== null) {
            $onProgress($manifest);
        }

        $rootScopeUrl = $manifest->baseUrl;
        $rootHost = (string) parse_url($rootScopeUrl, PHP_URL_HOST);

        $initialLocalPath = $this->assetRewriter->toLocalPagePath($manifest->url, $rootScopeUrl);
        /** @var array<string, string> $pageQueue URL => localPath */
        $pageQueue = [$this->urlNormalizer->normalize($manifest->url) => $initialLocalPath];

        /** @var array<string, string> $assetQueue URL => localPath */
        $assetQueue = [];

        /** @var array<string, bool> $visitedPages */
        $visitedPages = [];

        /** @var array<string, bool> $visitedAssets */
        $visitedAssets = [];

        $discoveredPagesList = [];

        try {
            // Process Pages & Assets while queues are not empty
            while (! empty($pageQueue) || ! empty($assetQueue)) {
                // 1. Process Asset Queue FIRST so stylesheets and media are downloaded immediately
                if (! empty($assetQueue)) {
                    $assetUrl = (string) array_key_first($assetQueue);
                    $localAssetPath = $assetQueue[$assetUrl];
                    unset($assetQueue[$assetUrl]);

                    if (isset($visitedAssets[$assetUrl])) {
                        continue;
                    }
                    $visitedAssets[$assetUrl] = true;

                    // If file already exists locally, skip downloading
                    if ($this->storage->hasFile($manifest->id, $localAssetPath)) {
                        continue;
                    }

                    $manifest->currentFile = $localAssetPath;
                    $manifest->elapsedSeconds = time() - $startTime;

                    try {
                        $response = $this->httpClient->get($assetUrl);
                        $statusCode = $response->getStatusCode();

                        if ($statusCode >= 200 && $statusCode < 400) {
                            $body = (string) $response->getBody();
                            $contentType = strtolower($response->getHeaderLine('Content-Type'));
                            $isCss = str_contains($contentType, 'text/css') || str_ends_with(strtolower($localAssetPath), '.css');

                            if ($isCss) {
                                // Parse CSS and collect embedded fonts / images
                                $body = $this->assetRewriter->rewriteCss(
                                    css: $body,
                                    currentCssUrl: $assetUrl,
                                    rootHost: $rootHost,
                                    siteId: $manifest->id,
                                    onAssetFound: function (string $embeddedAssetUrl, string $embeddedLocalPath) use (&$assetQueue, $visitedAssets): void {
                                        if (! isset($visitedAssets[$embeddedAssetUrl]) && ! isset($assetQueue[$embeddedAssetUrl])) {
                                            $assetQueue[$embeddedAssetUrl] = $embeddedLocalPath;
                                        }
                                    }
                                );
                            }

                            $bytesWritten = $this->storage->saveFile($manifest->id, $localAssetPath, $body);
                            $manifest->filesCount++;
                            $manifest->totalBytes += $bytesWritten;
                        } elseif ($statusCode === 429) {
                            $this->addLog($manifest, 'Лимит запросов (429) при загрузке ассета. Пауза 2с...', 'warning');
                            sleep(2);
                        } else {
                            $this->addLog($manifest, "Ошибка {$statusCode} при загрузке ассета: {$assetUrl}", 'warning');
                        }
                    } catch (GuzzleException $e) {
                        $this->addLog($manifest, "Ошибка сети при загрузке {$assetUrl}: {$e->getMessage()}", 'warning');
                    }

                    $this->updateProgress($manifest, $onProgress);
                    usleep(20000);

                    continue;
                }

                // 2. Process Page Queue
                $pageUrl = (string) array_key_first($pageQueue);
                $localPagePath = $pageQueue[$pageUrl];
                unset($pageQueue[$pageUrl]);

                if (isset($visitedPages[$pageUrl])) {
                    continue;
                }
                $visitedPages[$pageUrl] = true;

                $manifest->currentFile = $localPagePath;
                $manifest->elapsedSeconds = time() - $startTime;

                try {
                    $response = $this->httpClient->get($pageUrl);
                    $statusCode = $response->getStatusCode();

                    if ($statusCode >= 200 && $statusCode < 400) {
                        $html = (string) $response->getBody();

                        // Rewrite HTML and collect referenced assets & pages
                        $rewrittenHtml = $this->assetRewriter->rewriteHtml(
                            html: $html,
                            currentUrl: $pageUrl,
                            rootScopeUrl: $rootScopeUrl,
                            siteId: $manifest->id,
                            onAssetFound: function (string $assetUrl, string $localPath) use (&$assetQueue, $visitedAssets): void {
                                if (! isset($visitedAssets[$assetUrl]) && ! isset($assetQueue[$assetUrl])) {
                                    $assetQueue[$assetUrl] = $localPath;
                                }
                            },
                            onPageFound: function (string $newPageUrl, string $newLocalPath) use (&$pageQueue, $visitedPages): void {
                                $normalized = $this->urlNormalizer->normalize($newPageUrl);
                                if ($this->maxPages !== null && count($visitedPages) + count($pageQueue) >= $this->maxPages) {
                                    return;
                                }
                                if (! isset($visitedPages[$normalized]) && ! isset($pageQueue[$normalized])) {
                                    $pageQueue[$normalized] = $newLocalPath;
                                }
                            }
                        );

                        $bytesWritten = $this->storage->saveFile($manifest->id, $localPagePath, $rewrittenHtml);
                        $manifest->filesCount++;
                        $manifest->totalBytes += $bytesWritten;
                        $manifest->pagesCount++;
                        $discoveredPagesList[] = $localPagePath;
                        $manifest->pages = array_values(array_unique($discoveredPagesList));

                        $this->addLog($manifest, "Страница загружена [{$statusCode}]: {$localPagePath}", 'info');
                    } elseif ($statusCode === 429) {
                        $this->addLog($manifest, 'Лимит запросов (429) от сервера. Пауза 2с...', 'warning');
                        sleep(2);
                    } else {
                        $this->addLog($manifest, "Ошибка HTTP {$statusCode} при загрузке страницы: {$pageUrl}", 'warning');
                    }
                } catch (GuzzleException $e) {
                    $this->addLog($manifest, "Не удалось загрузить страницу {$pageUrl}: {$e->getMessage()}", 'warning');
                }

                $this->updateProgress($manifest, $onProgress);
                usleep(40000);
            }

            $manifest->status = 'completed';
            $manifest->completedAt = date('Y-m-d H:i:s');
            $manifest->currentFile = null;
            $manifest->elapsedSeconds = time() - $startTime;
            $this->addLog($manifest, 'Скачивание сайта успешно завершено!', 'info');
        } catch (\Throwable $e) {
            $manifest->status = 'failed';
            $manifest->errorMessage = $e->getMessage();
            $manifest->completedAt = date('Y-m-d H:i:s');
            $manifest->elapsedSeconds = time() - $startTime;
            $this->addLog($manifest, 'Фатальная ошибка: '.$e->getMessage(), 'error');
        }

        $this->storage->saveManifest($manifest);
        if ($onProgress !== null) {
            $onProgress($manifest);
        }

        return $manifest;
    }

    private function updateProgress(SiteManifest $manifest, ?callable $onProgress): void
    {
        $this->storage->saveManifest($manifest);
        if ($onProgress !== null) {
            $onProgress($manifest);
        }
    }

    private function addLog(SiteManifest $manifest, string $message, string $level = 'info'): void
    {
        $manifest->logs[] = [
            'time' => date('H:i:s'),
            'message' => $message,
            'level' => $level,
        ];

        // Keep last 100 log entries
        if (count($manifest->logs) > 100) {
            array_shift($manifest->logs);
        }
    }
}

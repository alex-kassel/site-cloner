<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Http\Controllers;

use AlexKassel\SiteCloner\Data\SiteManifest;
use AlexKassel\SiteCloner\Services\SiteClonerEngine;
use AlexKassel\SiteCloner\Services\SiteSlugGenerator;
use AlexKassel\SiteCloner\Services\SiteStorage;
use AlexKassel\SiteCloner\Services\UrlNormalizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DashboardController
{
    public function __construct(
        private SiteStorage $storage,
        private SiteSlugGenerator $slugGenerator,
        private UrlNormalizer $urlNormalizer,
        private SiteClonerEngine $engine,
    ) {}

    public function index(): View
    {
        $sites = $this->storage->allSites();
        $totalBytes = array_sum(array_map(static fn (SiteManifest $s): int => $s->totalBytes, $sites));

        $totalSizeFormatted = (new SiteManifest(
            id: '',
            url: '',
            baseUrl: '',
            scopePath: '',
            totalBytes: $totalBytes
        ))->humanReadableSize();

        return view('site-cloner::dashboard', [
            'sites' => $sites,
            'totalSites' => count($sites),
            'totalSizeFormatted' => $totalSizeFormatted,
            'routePrefix' => config('site-cloner.route_prefix', 'site-cloner'),
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $url = trim((string) $request->input('url', ''));
        if ($url === '') {
            return response()->json(['error' => 'URL is required'], 422);
        }

        $id = $this->slugGenerator->generate($url);
        $exists = $this->storage->exists($id);
        $manifest = $exists ? $this->storage->getManifest($id) : null;

        return response()->json([
            'id' => $id,
            'exists' => $exists,
            'manifest' => $manifest?->toArray(),
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $url = trim((string) $request->input('url', ''));
        $force = (bool) $request->input('force', false);

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return response()->json(['error' => 'Пожалуйста, введите корректный URL (например, https://example.com/demo/)'], 422);
        }

        $id = $this->slugGenerator->generate($url);

        if ($this->storage->exists($id) && ! $force) {
            return response()->json([
                'exists' => true,
                'id' => $id,
                'message' => 'Этот сайт уже был скачан ранее.',
                'manifest' => $this->storage->getManifest($id)?->toArray(),
            ], 409);
        }

        $normalizedUrl = $this->urlNormalizer->normalize($url);
        $scopePath = $this->urlNormalizer->getScopePath($normalizedUrl);
        $parsed = parse_url($normalizedUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $baseUrl = "{$scheme}://{$host}{$port}{$scopePath}";

        $manifest = new SiteManifest(
            id: $id,
            url: $url,
            baseUrl: $baseUrl,
            scopePath: $scopePath,
            status: 'pending',
            startedAt: date('Y-m-d H:i:s'),
        );

        $this->storage->saveManifest($manifest);

        return response()->json([
            'success' => true,
            'id' => $id,
            'manifest' => $manifest->toArray(),
        ]);
    }

    public function progress(string $siteId): StreamedResponse
    {
        return new StreamedResponse(function () use ($siteId): void {
            // Disable output buffering
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            $manifest = $this->storage->getManifest($siteId);
            if ($manifest === null) {
                echo "event: error\ndata: ".json_encode(['error' => 'Сайт не найден'])."\n\n";
                flush();

                return;
            }

            // If already completed or failed, emit current status once and finish
            if ($manifest->status === 'completed' || $manifest->status === 'failed') {
                echo 'data: '.json_encode($manifest->toArray())."\n\n";
                flush();

                return;
            }

            // If pending or downloading, run/attach the cloner engine
            $this->engine->clone($manifest, function (SiteManifest $currentManifest): void {
                echo 'data: '.json_encode($currentManifest->toArray())."\n\n";
                flush();
            });

            // Send completion event
            $finalManifest = $this->storage->getManifest($siteId) ?? $manifest;
            echo 'data: '.json_encode($finalManifest->toArray())."\n\n";
            echo "event: done\ndata: {}\n\n";
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function destroy(string $siteId): JsonResponse
    {
        $deleted = $this->storage->deleteSite($siteId);

        return response()->json([
            'success' => $deleted,
            'id' => $siteId,
        ]);
    }
}

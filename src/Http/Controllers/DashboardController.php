<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Http\Controllers;

use AlexKassel\SiteCloner\Data\SiteManifest;
use AlexKassel\SiteCloner\Services\ResourceInspector;
use AlexKassel\SiteCloner\Services\SiteClonerEngine;
use AlexKassel\SiteCloner\Services\SiteSlugGenerator;
use AlexKassel\SiteCloner\Services\SiteStorage;
use AlexKassel\SiteCloner\Services\UrlNormalizer;
use GuzzleHttp\Client;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DashboardController
{
    public function __construct(
        private SiteStorage $storage,
        private SiteSlugGenerator $slugGenerator,
        private UrlNormalizer $urlNormalizer,
        private SiteClonerEngine $engine,
    ) {}

    public function index(Request $request): View
    {
        $this->applyLocale($request);

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
            'currentLocale' => app()->getLocale(),
        ]);
    }

    public function setLocale(string $locale): RedirectResponse
    {
        if (in_array($locale, ['en', 'de', 'ru'], true)) {
            session(['site_cloner_locale' => $locale]);
            app()->setLocale($locale);
        }

        return redirect()->back();
    }

    private function applyLocale(Request $request): void
    {
        $queryLocale = $request->query('lang');
        $sessionLocale = session('site_cloner_locale', 'en');
        $locale = is_string($queryLocale) ? $queryLocale : (is_string($sessionLocale) ? $sessionLocale : 'en');

        if (in_array($locale, ['en', 'de', 'ru'], true)) {
            session(['site_cloner_locale' => $locale]);
            app()->setLocale($locale);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $url = trim((string) $request->input('url', ''));
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return response()->json(['error' => __('site-cloner::api.invalid_url')], 422);
        }

        $id = $this->slugGenerator->generate($url);
        $exists = $this->storage->exists($id);
        $manifest = $exists ? $this->storage->getManifest($id) : null;

        if ($exists) {
            return response()->json([
                'id' => $id,
                'exists' => true,
                'manifest' => $manifest?->toArray(),
            ]);
        }

        // Pre-flight reachability probe
        $proxy = config('site-cloner.proxy');
        $proxyUrl = is_string($proxy) && trim($proxy) !== '' ? trim($proxy) : null;
        $timeout = (int) config('site-cloner.timeout', 15);
        $userAgent = (string) config('site-cloner.user_agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)');

        $client = new Client([
            'timeout' => min($timeout, 15),
            'verify' => false,
            'http_errors' => false,
            'headers' => [
                'User-Agent' => $userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);

        try {
            $response = $client->get($url);
            $statusCode = $response->getStatusCode();

            // Retry via proxy if 403 or 429 and proxy is configured
            if (in_array($statusCode, [403, 429], true) && $proxyUrl !== null) {
                $response = $client->get($url, ['proxy' => $proxyUrl]);
                $statusCode = $response->getStatusCode();
            }

            if ($statusCode === 403) {
                $cfMitigated = $response->getHeaderLine('cf-mitigated');
                $isCloudflare = strtolower($cfMitigated) === 'challenge' || str_contains(strtolower($response->getHeaderLine('server')), 'cloudflare');

                $error = $isCloudflare
                    ? 'Target website is protected by Cloudflare/WAF and blocked this server IP (HTTP 403). Set SITE_CLONER_PROXY (e.g. socks5h://... or http://...) to bypass datacenter IP restrictions.'
                    : 'Target website returned HTTP 403 Forbidden. Configure SITE_CLONER_PROXY if your server IP is blocked.';

                return response()->json(['error' => $error], 422);
            }
        } catch (\Throwable) {
            if ($proxyUrl !== null) {
                try {
                    $response = $client->get($url, ['proxy' => $proxyUrl]);
                    if ($response->getStatusCode() === 403) {
                        return response()->json([
                            'error' => 'Target website returned HTTP 403 Forbidden even via proxy.',
                        ], 422);
                    }
                } catch (\Throwable) {
                    // Ignore connection errors during pre-flight
                }
            }
        }

        return response()->json([
            'id' => $id,
            'exists' => false,
            'manifest' => null,
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $this->applyLocale($request);

        $url = trim((string) $request->input('url', ''));
        $force = (bool) $request->input('force', false);

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return response()->json(['error' => __('site-cloner::api.invalid_url')], 422);
        }

        $id = $this->slugGenerator->generate($url);

        if ($this->storage->exists($id) && ! $force) {
            return response()->json([
                'exists' => true,
                'id' => $id,
                'message' => __('site-cloner::api.already_downloaded'),
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

        $mode = (string) $request->input('mode', 'light');
        if (! in_array($mode, ['light', 'full'], true)) {
            $mode = 'light';
        }

        $manifest = new SiteManifest(
            id: $id,
            url: $url,
            baseUrl: $baseUrl,
            scopePath: $scopePath,
            status: 'pending',
            startedAt: date('Y-m-d H:i:s'),
            downloadMode: $mode,
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
            set_time_limit(0);
            ignore_user_abort(true);

            // Release session lock so concurrent/polling requests are not blocked
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            // Disable all levels of output buffering
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Send unbuffer padding to defeat FastCGI buffer immediately
            echo ': '.str_repeat(' ', 4096)."\n\n";
            flush();

            $manifest = $this->storage->getManifest($siteId);
            if ($manifest === null) {
                echo "event: error\ndata: ".json_encode(['error' => __('site-cloner::api.site_not_found')])."\n\n";
                flush();

                return;
            }

            // Emit current initial status immediately so UI updates without waiting for first file
            echo 'data: '.json_encode($manifest->toArray())."\n\n";
            flush();

            // If already completed or failed, finish
            if ($manifest->status === 'completed' || $manifest->status === 'failed') {
                echo "event: done\ndata: {}\n\n";
                flush();

                return;
            }

            // Run cloner engine with live progress updates
            $this->engine->clone($manifest, function (SiteManifest $currentManifest): void {
                echo 'data: '.json_encode($currentManifest->toArray())."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            });

            // Send completion event
            $finalManifest = $this->storage->getManifest($siteId) ?? $manifest;
            echo 'data: '.json_encode($finalManifest->toArray())."\n\n";
            echo "event: done\ndata: {}\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function status(string $siteId): JsonResponse
    {
        $manifest = $this->storage->getManifest($siteId);
        if ($manifest === null) {
            return response()->json(['error' => __('site-cloner::api.site_not_found')], 404);
        }

        return response()->json($manifest->toArray());
    }

    public function resources(string $siteId, Request $request, ResourceInspector $inspector): JsonResponse
    {
        $this->applyLocale($request);

        $manifest = $this->storage->getManifest($siteId);
        if ($manifest === null) {
            return response()->json(['error' => __('site-cloner::api.site_not_found')], 404);
        }

        $refresh = (bool) $request->input('refresh', false);
        $data = $inspector->inspect($siteId, forceRefresh: $refresh);

        return response()->json([
            'id' => $siteId,
            'summary' => $data['summary'],
            'domains' => array_values($data['domains']),
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

    public function export(string $siteId, Request $request): BinaryFileResponse|JsonResponse
    {
        $this->applyLocale($request);

        $manifest = $this->storage->getManifest($siteId);
        if ($manifest === null) {
            return response()->json(['error' => __('site-cloner::api.site_not_found')], 404);
        }

        $zipPath = $this->storage->createZip($siteId);
        if ($zipPath === null || ! file_exists($zipPath)) {
            return response()->json(['error' => __('site-cloner::api.zip_failed')], 500);
        }

        return response()->download($zipPath, $siteId.'.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}

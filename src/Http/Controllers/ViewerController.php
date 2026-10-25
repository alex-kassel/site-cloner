<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Http\Controllers;

use AlexKassel\SiteCloner\Services\SiteStorage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

final class ViewerController
{
    public function __construct(
        private SiteStorage $storage,
    ) {}

    public function serve(string $siteId, ?string $path = null): Response
    {
        $path = $path ?? 'index.html';
        $path = ltrim($path, '/');

        if ($path === '' || str_ends_with($path, '/')) {
            $path .= 'index.html';
        }

        // Prevent path traversal attacks
        if (str_contains($path, '..') || str_contains($path, '\\')) {
            return new Response('Доступ запрещен', 403);
        }

        $fullPath = $this->storage->getFilePath($siteId, $path);

        // If file does not exist, try with .html extension
        if (! file_exists($fullPath) && ! str_contains(basename($path), '.')) {
            $candidate = $fullPath.'.html';
            if (file_exists($candidate)) {
                $fullPath = $candidate;
            }
        }

        if (! file_exists($fullPath) || is_dir($fullPath)) {
            return new Response(
                view('site-cloner::not-found', [
                    'siteId' => $siteId,
                    'path' => $path,
                    'manifest' => $this->storage->getManifest($siteId),
                ])->render(),
                404,
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }

        $mimeType = $this->detectMimeType($fullPath);

        $response = new BinaryFileResponse($fullPath);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Cache-Control', 'public, max-age=3600');

        return $response;
    }

    private function detectMimeType(string $file): string
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return match ($extension) {
            'html', 'htm' => 'text/html; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'js', 'mjs' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'font/otf',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mp3' => 'audio/mpeg',
            'pdf' => 'application/pdf',
            default => mime_content_type($file) ?: 'application/octet-stream',
        };
    }
}

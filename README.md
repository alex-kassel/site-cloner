# SiteCloner

<p align="center">
  <a href="AUDIT.json"><img src="https://img.shields.io/badge/Audit-Verified-10b981?logo=shield" alt="Audit Verified"></a>
  <a href="https://packagist.org/packages/alex-kassel/site-cloner"><img src="https://img.shields.io/packagist/v/alex-kassel/site-cloner?color=f59e0b&logo=packagist&logoColor=white" alt="Latest Version"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-ff2d20?logo=laravel&logoColor=white" alt="Laravel Support"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2+-777bb4?logo=php&logoColor=white" alt="PHP Support"></a>
  <a href="phpstan.neon"><img src="https://img.shields.io/badge/PHPStan-Level%208-8b5cf6?logo=php&logoColor=white" alt="PHPStan Level 8"></a>
</p>

SiteCloner is a Laravel package designed to download, clone, and offline-browse full websites or demo templates with local asset rewriting, resource inspection, and multi-language support.

## Key Features

- **Recursive Crawler**: Recursively downloads web pages, stylesheets, scripts, fonts, and images.
- **Offline Asset Rewriting**: Rewrites asset URLs to relative paths for offline viewing and local browsing.
- **Interactive Web Dashboard**: Built-in UI to trigger cloning, view live progress, inspect external resources, and browse archives.
- **Resource Inspector**: Analyzes downloaded assets, external CDNs, and deactivated links with summary metrics.
- **Multi-Language Support**: Complete localization for English, German, and Russian (`en`, `de`, `ru`).
- **Archive Export**: Export cloned sites as standalone ZIP archives.

## Requirements

- PHP: `^8.2`
- illuminate/support: `^11.0|^12.0|^13.0`
- illuminate/http: `^11.0|^12.0|^13.0`

## Installation

Install the package via Composer:

```bash
composer require alex-kassel/site-cloner
```

Optionally publish the configuration file:

```bash
php artisan vendor:publish --tag="site-cloner-config"
```

## Usage

### Web Dashboard & Viewer

By default, SiteCloner registers web routes accessible at:

- **Dashboard**: `http://your-app.test/site-cloner`
- **Viewer**: `http://your-app.test/site-cloner/view/{site-id}`

### Programmatic Usage

You can also inject and use `SiteClonerEngine` directly in your code:

```php
use AlexKassel\SiteCloner\Services\SiteClonerEngine;

$engine = app(SiteClonerEngine::class);

$manifest = $engine->cloneSite(
    url: 'https://example.com/demo/',
    maxPages: 10,
    progressCallback: function (string $status, string $message): void {
        // Handle progress updates
    }
);
```

## Configuration

The published `config/site-cloner.php` allows configuring:

```php
return [
    // Route prefix for dashboard and viewer (default: 'site-cloner')
    'route_prefix' => env('SITE_CLONER_ROUTE_PREFIX', 'site-cloner'),

    // Middleware applied to cloner routes (default: ['web'])
    'middleware' => ['web'],

    // Storage path for cloned sites (defaults to storage_path('app/site-cloner/sites'))
    'storage_path' => env('SITE_CLONER_STORAGE_PATH', null),

    // Request timeout in seconds
    'timeout' => 30,

    // User-Agent string used during downloads
    'user_agent' => 'SiteCloner/1.0',

    // Domains kept as external online links rather than downloaded
    'external_cdn_hosts' => [
        'fonts.googleapis.com',
        'fonts.gstatic.com',
        'fonts.bunny.net',
    ],
];
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [Security Policies](https://github.com/alex-kassel/site-cloner/security/policy) on how to report vulnerabilities.

## License

The MIT License (MIT). Copyright (c) 2026 alex-kassel.

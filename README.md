<h1 align="center">🌐 SiteCloner</h1>

<p align="center">
  <strong>Full website and demo-template offline cloner for Laravel with asset rewriting, resource inspection, and multi-language support.</strong>
</p>

<p align="center">
  <a href="#why-this-exists">Why This Exists</a> •
  <a href="#requirements">Requirements</a> •
  <a href="#installation">Installation</a> •
  <a href="#quickstart">Quickstart</a> •
  <a href="#usage--recipes">Usage</a> •
  <a href="#reference">Reference</a> •
  <a href="CHANGELOG.md">Changelog</a>
</p>

<p align="center">
  <a href="AUDIT.json"><img src="https://img.shields.io/badge/Audit-Verified-10b981?logo=shield" alt="Audit Verified"></a>
  <a href="https://packagist.org/packages/alex-kassel/site-cloner"><img src="https://img.shields.io/packagist/v/alex-kassel/site-cloner?color=f59e0b&logo=packagist&logoColor=white" alt="Latest Version"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-ff2d20?logo=laravel&logoColor=white" alt="Laravel Support"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2+-777bb4?logo=php&logoColor=white" alt="PHP Support"></a>
  <a href="phpstan.neon"><img src="https://img.shields.io/badge/PHPStan-Level%208-8b5cf6?logo=php&logoColor=white" alt="PHPStan Level 8"></a>
</p>

---

## Why This Exists

When prototyping web applications, researching competitor interfaces, or integrating third-party HTML/CSS themes, developers often need to inspect or serve complete websites locally. However, standard browser saving (`Ctrl+S` or `wget`) produces broken relative paths, CORS-blocked font requests, missing CDN stylesheets, and active tracking scripts.

**SiteCloner** solves this for Laravel applications:
- **Autonomous Asset Rewriting:** Downloads all dependent stylesheets, scripts, fonts, and images, rewriting URLs to consistent local relative paths that work seamlessly both via HTTP and offline `file://` protocols.
- **Controlled Scope & Boundary Isolation:** Keeps external links functional while preventing crawler escapes, keeping your local archive clean and deterministic.
- **Embedded Management UI:** Provides an interactive dashboard for queuing downloads, inspecting external CDN dependencies, previewing sites in an isolated sandbox, and exporting self-contained ZIP archives.

---

## Key Features

- **Recursive Crawler**: Crawls web pages within domain scope, respecting configurable recursion limits and page bounds.
- **Offline Asset Rewriter**: Rewrites CSS `url(...)`, HTML `src`/`href`, `srcset`, and inline styles for 100% offline local browsing.
- **Interactive Dashboard**: Modern responsive UI with live streaming progress, card/list archive views, and disk usage metrics.
- **Resource Inspector**: Deep-scans cloned sites to categorize local assets, active external CDNs, and uncloned links.
- **Multi-Language Support**: Fully localized in English, German, and Russian (`en`, `de`, `ru`) with instant locale switching.
- **ZIP Export**: One-click export of any cloned site into a clean, distributable ZIP archive.

---

## Requirements

- **PHP**: `^8.2`, `^8.3`, or `^8.4`
- **Laravel Framework**: `^11.0`, `^12.0`, or `^13.0`
- **PHP Extensions**: `ext-dom`, `ext-curl`, `ext-libxml`, `ext-zip`

---

## Installation

Install the package via Composer:

```bash
composer require alex-kassel/site-cloner
```

The package automatically registers its ServiceProvider via Laravel package discovery.

### Publishing Configuration (Optional)

```bash
php artisan vendor:publish --tag="site-cloner-config"
```

### Publishing Views & Translations (Optional)

```bash
# Publish customizable Blade templates
php artisan vendor:publish --tag="site-cloner-views"

# Publish translation files (en, de, ru)
php artisan vendor:publish --tag="site-cloner-translations"
```

---

## Quickstart

Start cloning and viewing sites in under 60 seconds:

1. Visit the dashboard at `http://your-app.test/site-cloner` (or your configured route prefix).
2. Enter any URL (e.g. `https://bootstrapmade.com/content/demo/AppDashboard/`).
3. Click **Download / Clone** and watch real-time progress.
4. Click **View Site** to browse the fully offline cloned version at `/site-cloner/view/{site-id}`.

---

## Usage & Recipes

### 1. Programmatic Site Cloning

You can inject and invoke `SiteClonerEngine` directly in your jobs, commands, or services:

```php
use AlexKassel\SiteCloner\Services\SiteClonerEngine;

class CloneDemoSiteJob
{
    public function handle(SiteClonerEngine $cloner): void
    {
        $manifest = $cloner->cloneSite(
            url: 'https://example.com/demo/',
            maxPages: 10,
            progressCallback: function (string $status, string $message): void {
                logger()->info("[SiteCloner: {$status}] {$message}");
            }
        );

        echo "Cloned site ID: {$manifest->id}\n";
        echo "Total pages: " . count($manifest->pages) . "\n";
    }
}
```

### 2. Inspecting External & Local Resources

Analyze what third-party services and CDNs a cloned site depends on:

```php
use AlexKassel\SiteCloner\Services\ResourceInspector;

$inspector = app(ResourceInspector::class);
$report = $inspector->inspect('example-com-demo');

// $report['summary'] => ['total_domains' => 3, 'downloaded_count' => 12, 'external_count' => 2, ...]
// $report['domains'] => Breakdown of each domain (fonts.googleapis.com, cdn.jsdelivr.net, etc.)
```

### 3. Serving & Viewer Security

The viewer route safely serves cloned HTML and assets with MIME-type detection and path-traversal prevention:

- `GET /site-cloner/view/{siteId}` — Serves root `index.html`.
- `GET /site-cloner/view/{siteId}/{path}` — Serves assets or subpages (`styles/app.css`, `page-2.html`).

---

## Reference

### Configuration Options

Published in `config/site-cloner.php`:

| Key | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `route_prefix` | `string` | `'site-cloner'` | Base URI prefix for the dashboard and viewer routes. |
| `middleware` | `array` | `['web']` | Middleware stack applied to all SiteCloner routes (add `auth` for admin-only access). |
| `storage_path` | `?string` | `null` | Absolute storage path on disk (defaults to `storage_path('app/site-cloner/sites')`). |
| `timeout` | `int` | `30` | HTTP request timeout in seconds for crawling pages and assets. |
| `user_agent` | `string` | Chrome 129 UA | Custom User-Agent string sent with crawl requests. |
| `external_cdn_hosts` | `array` | Google & Bunny Fonts | Hosts excluded from local download and kept as live external links. |

### Web Route Reference

| Method | URI | Route Name | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/{prefix}` | `site-cloner.index` | Main dashboard displaying all archives and clone form. |
| `GET` | `/{prefix}/locale/{lang}` | `site-cloner.locale` | Switch dashboard language (`en`, `de`, `ru`). |
| `POST` | `/{prefix}/check` | `site-cloner.check` | Checks if a target URL has already been cloned. |
| `POST` | `/{prefix}/start` | `site-cloner.start` | Initiates site cloning process. |
| `GET` | `/{prefix}/progress/{id}` | `site-cloner.progress` | Polls current cloning progress and step logs. |
| `GET` | `/{prefix}/resources/{id}`| `site-cloner.resources`| Returns JSON resource inspection metrics. |
| `GET` | `/{prefix}/export/{id}` | `site-cloner.export` | Downloads the site as a standalone `.zip` file. |
| `DELETE` | `/{prefix}/sites/{id}` | `site-cloner.destroy` | Deletes the archive and directory from disk. |
| `GET` | `/{prefix}/view/{id}/{path?}` | `site-cloner.view` | Securely serves cloned HTML pages and assets. |

---

## Testing

Run the package test suite:

```bash
composer test
```

Or run PHPUnit directly:

```bash
vendor/bin/phpunit
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on recent changes.

## Security Vulnerabilities

Please review our [Security Policy](https://github.com/alex-kassel/site-cloner/security/policy) for details on reporting vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

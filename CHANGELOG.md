# Changelog

All notable changes to `alex-kassel/site-cloner` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-10-25

### Added
- Native domain routing support via `config('site-cloner.domain')` / `SITE_CLONER_DOMAIN` environment variable to restrict routes to a specific subdomain or host.

## [1.0.0] - 2026-10-25

### Added
- Recursive web crawler engine for offline website cloning (`SiteClonerEngine`).
- Interactive web dashboard and viewer controllers with real-time progress streaming and archive management.
- Offline asset rewriter supporting relative paths and offline navigation (`AssetRewriter`).
- Resource inspection module categorizing downloaded assets, external CDNs, and deactivated links (`ResourceInspector`).
- Multi-language localization support for English, German, and Russian (`en`, `de`, `ru`).
- Standalone ZIP archive export for downloaded sites.
- Comprehensive PHPUnit test suite and PHPStan Level 8 static analysis compliance.
- Formal release audit certification with tamper-evident `AUDIT.json`.

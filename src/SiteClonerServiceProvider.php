<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner;

use AlexKassel\SiteCloner\Services\AssetRewriter;
use AlexKassel\SiteCloner\Services\SiteClonerEngine;
use AlexKassel\SiteCloner\Services\SiteSlugGenerator;
use AlexKassel\SiteCloner\Services\SiteStorage;
use AlexKassel\SiteCloner\Services\UrlNormalizer;
use Illuminate\Support\ServiceProvider;

class SiteClonerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/site-cloner.php',
            'site-cloner'
        );

        $this->app->singleton(SiteStorage::class, function ($app): SiteStorage {
            return new SiteStorage(
                basePath: (string) config('site-cloner.storage_path', storage_path('app/site-cloner/sites'))
            );
        });

        $this->app->singleton(UrlNormalizer::class, fn () => new UrlNormalizer);
        $this->app->singleton(SiteSlugGenerator::class, fn () => new SiteSlugGenerator);

        $this->app->singleton(AssetRewriter::class, function ($app): AssetRewriter {
            return new AssetRewriter(
                urlNormalizer: $app->make(UrlNormalizer::class),
                externalCdnHosts: (array) config('site-cloner.external_cdn_hosts', []),
                routePrefix: (string) config('site-cloner.route_prefix', 'site-cloner')
            );
        });

        $this->app->singleton(SiteClonerEngine::class, function ($app): SiteClonerEngine {
            return new SiteClonerEngine(
                storage: $app->make(SiteStorage::class),
                urlNormalizer: $app->make(UrlNormalizer::class),
                assetRewriter: $app->make(AssetRewriter::class),
                maxPages: config('site-cloner.max_pages'),
                timeout: (int) config('site-cloner.timeout', 30),
                userAgent: (string) config('site-cloner.user_agent', 'SiteCloner/1.0')
            );
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'site-cloner');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/site-cloner.php' => config_path('site-cloner.php'),
            ], 'site-cloner-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/site-cloner'),
            ], 'site-cloner-views');

            $skillsSource = is_dir(__DIR__.'/../resources/boost/skills')
                ? __DIR__.'/../resources/boost/skills'
                : __DIR__.'/../resources/skills';

            if (is_dir($skillsSource)) {
                $targetPaths = (array) config('site-cloner.skills_path', ['.agents/skills']);
                $publishes = [];

                foreach ($targetPaths as $targetPath) {
                    $publishes[$skillsSource] = base_path($targetPath);
                }

                $this->publishes($publishes, 'site-cloner-skills');
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner;

use Illuminate\Support\ServiceProvider;

class SiteClonerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/site-cloner.php',
            'site-cloner'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/site-cloner.php' => config_path('site-cloner.php'),
            ], 'site-cloner-config');

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

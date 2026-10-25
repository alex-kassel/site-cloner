<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Tests\Unit;

use AlexKassel\SiteCloner\Tests\TestCase;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;

final class RouteDomainTest extends TestCase
{
    public function test_domain_is_applied_to_routes_when_configured(): void
    {
        config([
            'site-cloner.domain' => 'tools.ab-box.de',
            'site-cloner.route_prefix' => 'site-cloner-domain-test',
        ]);

        require __DIR__.'/../../routes/web.php';

        $route = collect(Route::getRoutes())->first(
            static fn (LaravelRoute $r): bool => $r->uri() === 'site-cloner-domain-test'
        );

        $this->assertNotNull($route);
        $this->assertSame('tools.ab-box.de', $route->getDomain());
    }

    public function test_domain_is_omitted_when_null(): void
    {
        config([
            'site-cloner.domain' => null,
            'site-cloner.route_prefix' => 'site-cloner-null-domain-test',
        ]);

        require __DIR__.'/../../routes/web.php';

        $route = collect(Route::getRoutes())->first(
            static fn (LaravelRoute $r): bool => $r->uri() === 'site-cloner-null-domain-test'
        );

        $this->assertNotNull($route);
        $this->assertNull($route->getDomain());
    }
}

<?php

namespace YorCreative\UrlShortener\Tests\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use YorCreative\UrlShortener\Tests\TestCase;

class RouteRegistrationDisabledTest extends TestCase
{
    #[Test]
    #[Group('Feature')]
    #[Group('RoutingConfig')]
    public function it_does_not_register_package_routes_when_disabled()
    {
        $registered = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_ends_with($route->uri(), '{identifier}'));

        $this->assertCount(0, $registered);
    }

    #[Test]
    #[Group('Feature')]
    #[Group('RoutingConfig')]
    public function it_leaves_the_protected_route_name_available_for_the_consumer()
    {
        $this->assertFalse(Route::has('urlshortener.attempt.protected'));
    }

    #[Test]
    #[Group('Feature')]
    #[Group('RoutingConfig')]
    public function it_still_builds_short_urls_when_routes_are_disabled()
    {
        $this->assertNotEmpty($this->url);
        $this->assertNotEmpty($this->identifier);
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('urlshortener.routing.enabled', false);
    }
}

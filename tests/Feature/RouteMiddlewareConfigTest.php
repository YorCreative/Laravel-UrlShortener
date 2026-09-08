<?php

namespace YorCreative\UrlShortener\Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Middleware\ResolveDomain;
use YorCreative\UrlShortener\Tests\Middleware\MarksRequest;
use YorCreative\UrlShortener\Tests\TestCase;

class RouteMiddlewareConfigTest extends TestCase
{
    #[Test]
    #[Group('Feature')]
    #[Group('RoutingConfig')]
    public function it_defaults_to_the_web_middleware_group()
    {
        $this->assertSame(['web'], config('urlshortener.routing.middleware'));

        $this->assertTrue(
            $this->identifierRoutes()->contains(
                fn ($route) => in_array('web', $route->middleware(), true)
            )
        );
    }

    #[Test]
    #[Group('Feature')]
    #[Group('RoutingConfig')]
    public function it_applies_configured_middleware_to_package_routes()
    {
        config(['urlshortener.routing.middleware' => ['web', 'throttle:60,1']]);

        require dirname(__DIR__, 2).'/src/Utility/routes.php';

        $this->assertTrue(
            $this->identifierRoutes()->contains(
                fn ($route) => in_array('throttle:60,1', $route->middleware(), true)
            )
        );
    }

    #[Test]
    #[Group('Feature')]
    #[Group('RoutingConfig')]
    public function it_accepts_a_single_middleware_string()
    {
        config(['urlshortener.routing.middleware' => 'api']);

        require dirname(__DIR__, 2).'/src/Utility/routes.php';

        $this->assertTrue(
            $this->identifierRoutes()->contains(
                fn ($route) => in_array('api', $route->middleware(), true)
            )
        );
    }

    #[Test]
    #[Group('Feature')]
    #[Group('RoutingConfig')]
    public function it_still_appends_domain_middleware_when_multi_domain_is_enabled()
    {
        config([
            'urlshortener.domains.enabled' => true,
            'urlshortener.routing.middleware' => ['web', 'throttle:60,1'],
        ]);

        require dirname(__DIR__, 2).'/src/Utility/routes.php';

        $this->assertTrue(
            $this->identifierRoutes()->contains(
                fn ($route) => in_array('throttle:60,1', $route->middleware(), true)
                    && in_array(ResolveDomain::class, $route->middleware(), true)
            )
        );
    }

    #[Test]
    #[Group('Feature')]
    #[Group('RoutingConfig')]
    public function it_does_not_duplicate_domain_middleware_when_already_configured()
    {
        config([
            'urlshortener.domains.enabled' => true,
            'urlshortener.routing.middleware' => ['web', ResolveDomain::class],
        ]);

        require dirname(__DIR__, 2).'/src/Utility/routes.php';

        $route = $this->identifierRoutes()->last();

        $this->assertSame(
            1,
            count(array_keys($route->middleware(), ResolveDomain::class, true))
        );
    }

    #[Test]
    #[Group('Feature')]
    #[Group('RoutingConfig')]
    public function it_actually_executes_configured_middleware_on_a_request()
    {
        MarksRequest::reset();

        // A distinct prefix so this registration is the one that matches; the
        // provider already registered the default prefix without our middleware.
        config([
            'urlshortener.routing.additional_prefixes' => ['guarded'],
            'urlshortener.routing.middleware' => ['web', MarksRequest::class],
        ]);

        require dirname(__DIR__, 2).'/src/Utility/routes.php';

        $plainText = 'https://middleware-executes.test/'.rand(999, 999999);
        $url = UrlBuilder::shorten($plainText)->withPrefix('guarded')->build();

        $this->get('/guarded/'.$this->identifierFrom($url))
            ->assertRedirect($plainText);

        $this->assertTrue(
            MarksRequest::$ran,
            'Configured middleware was attached to the route but never executed.'
        );
    }

    #[Test]
    #[Group('Feature')]
    #[Group('RoutingConfig')]
    public function it_applies_configured_middleware_to_prefixless_domain_routes()
    {
        config([
            'urlshortener.domains.enabled' => true,
            'urlshortener.domains.resolution_strategy' => 'host',
            'urlshortener.domains.hosts' => ['bare.test' => ['prefix' => null]],
            'urlshortener.routing.middleware' => ['web', 'throttle:60,1'],
        ]);

        require dirname(__DIR__, 2).'/src/Utility/routes.php';

        $rootRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->uri() === '{identifier}')
            ->values();

        $this->assertNotEmpty(
            $rootRoutes,
            'No prefixless domain route was registered.'
        );

        $this->assertTrue(
            $rootRoutes->contains(
                fn ($route) => in_array('throttle:60,1', $route->middleware(), true)
                    && in_array(ResolveDomain::class, $route->middleware(), true)
            )
        );
    }

    protected function identifierFrom(string $url): string
    {
        $parts = explode('/', rtrim($url, '/'));

        return end($parts);
    }

    protected function identifierRoutes()
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_ends_with($route->uri(), '{identifier}'))
            ->values();
    }
}

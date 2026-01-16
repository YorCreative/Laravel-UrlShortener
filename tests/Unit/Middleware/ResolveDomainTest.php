<?php

namespace YorCreative\UrlShortener\Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use YorCreative\UrlShortener\Middleware\ResolveDomain;
use YorCreative\UrlShortener\Services\DomainResolver;
use YorCreative\UrlShortener\Tests\TestCase;

class ResolveDomainTest extends TestCase
{
    protected ResolveDomain $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ResolveDomain(new DomainResolver);
    }

    /**
     * @test
     *
     * @group Middleware
     * @group ResolveDomain
     */
    public function it_passes_through_when_domains_disabled()
    {
        config(['urlshortener.domains.enabled' => false]);

        $request = Request::create('https://any-domain.com/abc123');
        $called = false;

        $response = $this->middleware->handle($request, function ($req) use (&$called) {
            $called = true;

            return response('OK');
        });

        $this->assertTrue($called);
        $this->assertNull($request->attributes->get('urlshortener_domain'));
    }

    /**
     * @test
     *
     * @group Middleware
     * @group ResolveDomain
     */
    public function it_sets_domain_attribute_on_request()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'host']);
        config(['urlshortener.domains.hosts' => [
            'test.io' => ['prefix' => 't'],
        ]]);

        $request = Request::create('https://test.io/abc123');
        $called = false;

        $response = $this->middleware->handle($request, function ($req) use (&$called) {
            $called = true;

            return response('OK');
        });

        $this->assertTrue($called);
        $this->assertEquals('test.io', $request->attributes->get('urlshortener_domain'));
    }

    /**
     * @test
     *
     * @group Middleware
     * @group ResolveDomain
     */
    public function it_aborts_for_unallowed_domain_when_validation_enabled()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'host']);
        config(['urlshortener.domains.validate_domain' => true]);
        config(['urlshortener.domains.hosts' => [
            'allowed.io' => ['prefix' => 'a'],
        ]]);

        $request = Request::create('https://notallowed.io/abc123');

        $this->expectException(NotFoundHttpException::class);

        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /**
     * @test
     *
     * @group Middleware
     * @group ResolveDomain
     */
    public function it_allows_any_domain_when_validation_disabled()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'host']);
        config(['urlshortener.domains.validate_domain' => false]);
        config(['urlshortener.domains.hosts' => [
            'allowed.io' => ['prefix' => 'a'],
        ]]);

        $request = Request::create('https://anydomain.io/abc123');
        $called = false;

        $response = $this->middleware->handle($request, function ($req) use (&$called) {
            $called = true;

            return response('OK');
        });

        $this->assertTrue($called);
    }

    /**
     * @test
     *
     * @group Middleware
     * @group ResolveDomain
     */
    public function it_handles_null_domain_gracefully()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'subdomain']);
        config(['urlshortener.domains.subdomain.base_domain' => null]);

        $request = Request::create('https://test.com/abc123');
        $called = false;

        $response = $this->middleware->handle($request, function ($req) use (&$called) {
            $called = true;

            return response('OK');
        });

        $this->assertTrue($called);
        $this->assertNull($request->attributes->get('urlshortener_domain'));
    }

    /**
     * @test
     *
     * @group Middleware
     * @group ResolveDomain
     */
    public function it_resolves_domain_aliases()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'host']);
        config(['urlshortener.domains.hosts' => [
            'main.io' => ['prefix' => 'm'],
        ]]);
        config(['urlshortener.domains.aliases' => [
            'alias.io' => 'main.io',
        ]]);

        $request = Request::create('https://alias.io/abc123');
        $called = false;

        $this->middleware->handle($request, function ($req) use (&$called) {
            $called = true;

            return response('OK');
        });

        $this->assertEquals('main.io', $request->attributes->get('urlshortener_domain'));
    }
}

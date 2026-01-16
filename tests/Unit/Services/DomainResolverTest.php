<?php

namespace YorCreative\UrlShortener\Tests\Unit\Services;

use Illuminate\Http\Request;
use YorCreative\UrlShortener\Exceptions\DomainResolutionException;
use YorCreative\UrlShortener\Services\DomainResolver;
use YorCreative\UrlShortener\Tests\TestCase;

class DomainResolverTest extends TestCase
{
    protected DomainResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new DomainResolver;
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_returns_null_when_domains_disabled()
    {
        config(['urlshortener.domains.enabled' => false]);

        $request = Request::create('https://test.io/abc123');
        $result = $this->resolver->resolve($request);

        $this->assertNull($result);
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_resolves_domain_from_host_strategy()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'host']);

        $request = Request::create('https://test.io/abc123');
        $result = $this->resolver->resolve($request);

        $this->assertEquals('test.io', $result);
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_strips_www_prefix_from_host()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'host']);

        $request = Request::create('https://www.test.io/abc123');
        $result = $this->resolver->resolve($request);

        $this->assertEquals('test.io', $result);
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_resolves_domain_from_subdomain_strategy()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'subdomain']);
        config(['urlshortener.domains.subdomain.base_domain' => 'example.com']);

        $request = Request::create('https://test.example.com/abc123');
        $result = $this->resolver->resolve($request);

        $this->assertEquals('test', $result);
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_returns_null_for_subdomain_without_base_domain()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'subdomain']);
        config(['urlshortener.domains.subdomain.base_domain' => null]);

        $request = Request::create('https://test.example.com/abc123');
        $result = $this->resolver->resolve($request);

        $this->assertNull($result);
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_resolves_domain_from_path_strategy()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'path']);

        $request = Request::create('https://shortener.com/mycompany/abc123');
        $result = $this->resolver->resolve($request);

        $this->assertEquals('mycompany', $result);
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_resolves_domain_aliases()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'host']);
        config(['urlshortener.domains.aliases' => [
            'short.test.io' => 'test.io',
        ]]);

        $request = Request::create('https://short.test.io/abc123');
        $result = $this->resolver->resolve($request);

        $this->assertEquals('test.io', $result);
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_throws_exception_for_unknown_strategy()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'invalid_strategy']);

        $this->expectException(DomainResolutionException::class);
        $this->expectExceptionMessage('Unknown resolution strategy');

        $request = Request::create('https://test.io/abc123');
        $this->resolver->resolve($request);
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_builds_url_with_protocol()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.hosts' => [
            'test.io' => ['prefix' => 't'],
        ]]);

        $url = $this->resolver->buildUrl('abc123', 'test.io');

        $this->assertStringStartsWith('https://', $url);
        $this->assertStringContainsString('test.io', $url);
        $this->assertStringContainsString('abc123', $url);
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_builds_url_with_prefix()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.hosts' => [
            'test.io' => ['prefix' => 'go'],
        ]]);

        $url = $this->resolver->buildUrl('abc123', 'test.io');

        $this->assertEquals('https://test.io/go/abc123', $url);
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_builds_url_without_prefix()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.hosts' => [
            'test.io' => ['prefix' => null],
        ]]);

        $url = $this->resolver->buildUrl('abc123', 'test.io');

        $this->assertEquals('https://test.io/abc123', $url);
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_checks_if_domain_is_allowed_when_disabled()
    {
        config(['urlshortener.domains.enabled' => false]);

        $this->assertTrue($this->resolver->isAllowed('any-domain.com'));
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_checks_if_domain_is_allowed_from_config()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.use_database' => false]);
        config(['urlshortener.domains.hosts' => [
            'allowed.io' => ['prefix' => 'a'],
        ]]);

        $this->assertTrue($this->resolver->isAllowed('allowed.io'));
        $this->assertFalse($this->resolver->isAllowed('not-allowed.io'));
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_allows_null_domain_as_default()
    {
        config(['urlshortener.domains.enabled' => true]);

        $this->assertTrue($this->resolver->isAllowed(null));
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_gets_prefix_for_domain()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.hosts' => [
            'test.io' => ['prefix' => 'myprefix'],
        ]]);

        $this->assertEquals('myprefix', $this->resolver->getPrefix('test.io'));
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_gets_identifier_length_for_domain()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.hosts' => [
            'test.io' => ['identifier_length' => 8],
        ]]);

        $this->assertEquals(8, $this->resolver->getIdentifierLength('test.io'));
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_returns_default_identifier_length_when_not_configured()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.hosts' => [
            'test.io' => [],
        ]]);
        config(['urlshortener.branding.identifier.length' => 6]);

        $this->assertEquals(6, $this->resolver->getIdentifierLength('test.io'));
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_can_set_domain_manually()
    {
        $this->resolver->setDomain('manual.io');

        $this->assertEquals('manual.io', $this->resolver->current());
    }

    /**
     * @test
     *
     * @group DomainResolver
     */
    public function it_returns_current_resolved_domain()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'host']);

        $request = Request::create('https://current.io/abc123');
        $this->resolver->resolve($request);

        $this->assertEquals('current.io', $this->resolver->current());
    }
}

<?php

namespace YorCreative\UrlShortener\Tests\Feature;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Exceptions\UrlBuilderException;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Services\DomainResolver;
use YorCreative\UrlShortener\Services\UrlService;
use YorCreative\UrlShortener\Tests\TestCase;

class MultiDomainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Enable multi-domain for these tests
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.hosts' => [
            'test.io' => ['prefix' => 't', 'identifier_length' => 4],
            'link.co' => ['prefix' => 'l', 'identifier_length' => 6],
        ]]);
    }

    #[Test]
    #[Group('Feature')]
    #[Group('MultiDomain')]
    public function it_can_create_short_url_for_specific_domain()
    {
        $url = UrlBuilder::shorten('https://example.com/domain-test')
            ->forDomain('test.io')
            ->build();

        $this->assertStringStartsWith('https://test.io/t/', $url);
        $this->assertDatabaseHas('short_urls', [
            'domain' => 'test.io',
            'plain_text' => 'https://example.com/domain-test',
        ]);
    }

    #[Test]
    #[Group('Feature')]
    #[Group('MultiDomain')]
    public function same_identifier_can_exist_on_different_domains()
    {
        // Create short URL on domain A
        $urlA = UrlBuilder::shorten('https://site-a.com/page')
            ->forDomain('test.io')
            ->build();

        $identifierA = $this->extractIdentifier($urlA);

        // Manually create with same identifier on domain B
        $shortUrlB = ShortUrl::create([
            'domain' => 'link.co',
            'identifier' => $identifierA,
            'hashed' => md5('https://site-b.com/page'),
            'plain_text' => 'https://site-b.com/page',
        ]);

        // Both should exist with same identifier but different domains
        $this->assertDatabaseHas('short_urls', [
            'domain' => 'test.io',
            'identifier' => $identifierA,
            'plain_text' => 'https://site-a.com/page',
        ]);

        $this->assertDatabaseHas('short_urls', [
            'domain' => 'link.co',
            'identifier' => $identifierA,
            'plain_text' => 'https://site-b.com/page',
        ]);
    }

    #[Test]
    #[Group('Feature')]
    #[Group('MultiDomain')]
    public function it_can_find_by_identifier_with_domain()
    {
        $identifier = 'test123';

        // Create URLs with same identifier on different domains
        ShortUrl::create([
            'domain' => 'test.io',
            'identifier' => $identifier,
            'hashed' => md5('https://destination-a.com'),
            'plain_text' => 'https://destination-a.com',
        ]);

        ShortUrl::create([
            'domain' => 'link.co',
            'identifier' => $identifier,
            'hashed' => md5('https://destination-b.com'),
            'plain_text' => 'https://destination-b.com',
        ]);

        // Find by identifier with domain should return correct URL
        $foundA = UrlService::findByIdentifier($identifier, 'test.io');
        $foundB = UrlService::findByIdentifier($identifier, 'link.co');

        $this->assertEquals('https://destination-a.com', $foundA->plain_text);
        $this->assertEquals('https://destination-b.com', $foundB->plain_text);
    }

    #[Test]
    #[Group('Feature')]
    #[Group('MultiDomain')]
    public function it_uses_domain_specific_identifier_length()
    {
        // test.io is configured with identifier_length = 4
        $url = UrlBuilder::shorten('https://example.com/length-test')
            ->forDomain('test.io')
            ->build();

        $identifier = $this->extractIdentifier($url);
        $this->assertEquals(4, strlen($identifier));
    }

    #[Test]
    #[Group('Feature')]
    #[Group('MultiDomain')]
    public function it_uses_custom_prefix_override_when_building_domain_url()
    {
        config(['urlshortener.routing.additional_prefixes' => ['custom']]);

        $url = UrlBuilder::shorten('https://example.com/prefix-override')
            ->forDomain('test.io')
            ->withPrefix('custom')
            ->build();

        $this->assertStringStartsWith('https://test.io/custom/', $url);
    }

    #[Test]
    #[Group('Feature')]
    #[Group('MultiDomain')]
    public function it_rejects_unregistered_custom_prefix_override()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('not registered');

        UrlBuilder::shorten('https://example.com/unregistered-prefix')
            ->forDomain('test.io')
            ->withPrefix('custom')
            ->build();
    }

    #[Test]
    #[Group('Feature')]
    #[Group('MultiDomain')]
    public function it_rejects_empty_custom_prefix_override()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('cannot be empty');

        UrlBuilder::shorten('https://example.com/empty-prefix')
            ->forDomain('test.io')
            ->withPrefix('')
            ->build();
    }

    #[Test]
    #[Group('Feature')]
    #[Group('MultiDomain')]
    public function domain_resolver_builds_correct_url()
    {
        $resolver = app(DomainResolver::class);

        $url = $resolver->buildUrl('abc123', 'test.io');
        $this->assertEquals('https://test.io/t/abc123', $url);

        $url = $resolver->buildUrl('xyz789', 'link.co');
        $this->assertEquals('https://link.co/l/xyz789', $url);
    }

    #[Test]
    #[Group('Feature')]
    #[Group('MultiDomain')]
    public function it_can_find_all_urls_by_domain()
    {
        ShortUrl::create([
            'domain' => 'test.io',
            'identifier' => 'dom1',
            'hashed' => md5('https://url1.com'),
            'plain_text' => 'https://url1.com',
        ]);

        ShortUrl::create([
            'domain' => 'test.io',
            'identifier' => 'dom2',
            'hashed' => md5('https://url2.com'),
            'plain_text' => 'https://url2.com',
        ]);

        ShortUrl::create([
            'domain' => 'link.co',
            'identifier' => 'dom3',
            'hashed' => md5('https://url3.com'),
            'plain_text' => 'https://url3.com',
        ]);

        $testIoUrls = UrlService::findByDomain('test.io');
        $linkCoUrls = UrlService::findByDomain('link.co');

        $this->assertEquals(2, $testIoUrls->count());
        $this->assertEquals(1, $linkCoUrls->count());
    }

    #[Test]
    #[Group('Feature')]
    #[Group('MultiDomain')]
    public function it_works_in_single_domain_mode_when_disabled()
    {
        // Disable multi-domain
        config(['urlshortener.domains.enabled' => false]);

        $url = UrlBuilder::shorten('https://example.com/single-domain-test')
            ->build();

        // URL should be built successfully
        $this->assertNotEmpty($url);

        // Domain column should be null in single-domain mode
        $this->assertDatabaseHas('short_urls', [
            'domain' => null,
            'plain_text' => 'https://example.com/single-domain-test',
        ]);
    }

    #[Test]
    #[Group('Feature')]
    #[Group('MultiDomain')]
    public function find_or_create_respects_domain()
    {
        // Create a URL on test.io domain
        $builtUrl = UrlBuilder::shorten('https://example.com/findorcreate')
            ->forDomain('test.io')
            ->build();

        // findOrCreate with same URL and domain should return existing
        $result = UrlService::findOrCreate('https://example.com/findorcreate', 'test.io');
        $this->assertInstanceOf(ShortUrl::class, $result);

        // findOrCreate with same URL but different domain should return builder
        $result = UrlService::findOrCreate('https://example.com/findorcreate', 'link.co');
        $this->assertInstanceOf(UrlBuilder::class, $result);
    }

    #[Test]
    #[Group('Feature')]
    #[Group('MultiDomain')]
    public function it_routes_root_level_urls_for_domains_without_a_prefix()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.resolution_strategy' => 'host']);
        config(['urlshortener.domains.hosts' => [
            'root.test' => ['prefix' => null, 'identifier_length' => 6],
        ]]);

        require dirname(__DIR__, 2).'/src/Utility/routes.php';

        $plainText = 'https://root-destination.com/'.rand(999, 999999);
        $url = UrlBuilder::shorten($plainText)
            ->forDomain('root.test')
            ->build();

        // The URL is generated at the host root, with no prefix segment.
        $this->assertStringStartsWith('https://root.test/', $url);

        $identifier = $this->extractIdentifier($url);

        $this->assertStringNotContainsString('/', trim(str_replace('https://root.test/', '', $url), '/'));

        // The generated root-level URL must actually resolve.
        $this->get('https://root.test/'.$identifier)
            ->assertRedirect($plainText);
    }

    /**
     * Extract identifier from a built URL.
     */
    protected function extractIdentifier(string $url): string
    {
        // Remove trailing slash if present
        $url = rtrim($url, '/');

        // Get the last segment (identifier)
        $parts = explode('/', $url);

        return end($parts);
    }

    /**
     * Assert that a string contains another string.
     */
    protected function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '$haystack' contains '$needle'."
        );
    }
}

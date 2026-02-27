<?php

namespace YorCreative\UrlShortener\Tests\Unit\Builders\UrlBuilder;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Exceptions\UrlBuilderException;
use YorCreative\UrlShortener\Services\UrlService;
use YorCreative\UrlShortener\Tests\TestCase;

class VanityIdentifierTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     *
     * @group UrlBuilder
     */
    public function it_can_build_short_url_with_custom_identifier()
    {
        $plainText = 'https://example.com/vanity-test/'.rand(999, 999999);
        $customSlug = 'my-custom-slug';

        $url = UrlBuilder::shorten($plainText)
            ->withIdentifier($customSlug)
            ->build();

        $this->assertStringContainsString($customSlug, $url);
        $this->assertDatabaseHas('short_urls', [
            'identifier' => $customSlug,
            'plain_text' => $plainText,
        ]);
    }

    /**
     * @test
     *
     * @group UrlBuilder
     */
    public function it_rejects_empty_identifier()
    {
        $this->expectException(UrlBuilderException::class);

        UrlBuilder::shorten('https://example.com/empty-id/'.rand(999, 999999))
            ->withIdentifier('');
    }

    /**
     * @test
     *
     * @group UrlBuilder
     */
    public function it_rejects_identifier_exceeding_max_length()
    {
        $this->expectException(UrlBuilderException::class);

        UrlBuilder::shorten('https://example.com/long-id/'.rand(999, 999999))
            ->withIdentifier(str_repeat('a', 256));
    }

    /**
     * @test
     *
     * @group UrlBuilder
     */
    public function it_rejects_identifier_with_invalid_characters()
    {
        $this->expectException(UrlBuilderException::class);

        UrlBuilder::shorten('https://example.com/bad-chars/'.rand(999, 999999))
            ->withIdentifier('my slug with spaces');
    }

    /**
     * @test
     *
     * @group UrlBuilder
     */
    public function it_rejects_duplicate_custom_identifier()
    {
        $plainText1 = 'https://example.com/dup-test-1/'.rand(999, 999999);
        $plainText2 = 'https://example.com/dup-test-2/'.rand(999, 999999);
        $slug = 'unique-slug-'.rand(999, 999999);

        UrlBuilder::shorten($plainText1)
            ->withIdentifier($slug)
            ->build();

        $this->expectException(UrlBuilderException::class);

        UrlBuilder::shorten($plainText2)
            ->withIdentifier($slug)
            ->build();
    }
}

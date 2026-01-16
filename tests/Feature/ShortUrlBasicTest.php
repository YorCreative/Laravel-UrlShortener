<?php

namespace YorCreative\UrlShortener\Tests\Feature;

use YorCreative\UrlShortener\Services\UrlService;
use YorCreative\UrlShortener\Tests\TestCase;

class ShortUrlBasicTest extends TestCase
{
    /**
     * @test
     *
     * @group Feature
     */
    public function it_can_create_a_basic_short_url()
    {
        $this->assertDatabaseHas(
            'short_urls',
            [
                'plain_text' => $this->plain_text,
                'hashed' => $this->hashed,
                'identifier' => $this->identifier,
            ]
        );

        $shortUrl = UrlService::findByIdentifier($this->identifier);

        $this->assertDatabaseMissing('short_url_ownerships',
            [
                'short_url_id' => $shortUrl->id,
            ]
        );

        $this->assertDatabaseMissing('short_url_clicks',
            [
                'short_url_id' => $shortUrl->id,
            ]
        );
    }

    /**
     * @test
     *
     * @group Feature
     */
    public function it_returns_404_for_nonexistent_identifier()
    {
        $prefix = config('urlshortener.branding.prefix') ?? 'v1';
        $response = $this->get('/'.trim($prefix, '/').'/nonexistent_'.rand(999, 999999));

        $response->assertStatus(404);
    }

    /**
     * @test
     *
     * @group Feature
     */
    public function it_can_redirect_to_short_url()
    {
        // Create a fresh short URL in this test
        $plain_text = 'http://test-redirect.com/page/'.rand(1, 99999);
        $builtUrl = UrlService::shorten($plain_text)->build();

        $prefix = config('urlshortener.branding.prefix') ?? 'v1';
        $host = config('urlshortener.branding.host') ?? 'localhost.test';
        $base = rtrim($host, '/').'/'.trim($prefix, '/').'/';
        $identifier = str_replace($base, '', $builtUrl);

        $requestUrl = '/'.trim($prefix, '/').'/'.$identifier;

        $response = $this->get($requestUrl);

        $response->assertRedirect($plain_text);
    }
}

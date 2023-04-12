<?php

namespace YorCreative\UrlShortener\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use YorCreative\UrlShortener\Services\UrlService;
use YorCreative\UrlShortener\Tests\TestCase;

class ShortUrlBasicTest extends TestCase
{
    use DatabaseTransactions;

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
}

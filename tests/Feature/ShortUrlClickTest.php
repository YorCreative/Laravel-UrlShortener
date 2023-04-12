<?php

namespace YorCreative\UrlShortener\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Throwable;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Models\ShortUrlClick;
use YorCreative\UrlShortener\Services\ClickService;
use YorCreative\UrlShortener\Services\UrlService;
use YorCreative\UrlShortener\Tests\TestCase;

class ShortUrlClickTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     *
     * @group Feature
     *
     * @throws UrlRepositoryException
     * @throws Throwable
     */
    public function it_can_track_and_retrieve_successfully_routed_clicks()
    {
        // 5 successful routed
        ShortUrlClick::factory()->count(5)->create([
            'short_url_id' => UrlService::findByIdentifier($this->identifier)->id,
            'outcome_id' => ClickService::$SUCCESS_ROUTED,
        ]);

        // 5 successful protected
        ShortUrlClick::factory()->count(5)->create([
            'short_url_id' => UrlService::findByIdentifier($this->identifier)->id,
            'outcome_id' => ClickService::$SUCCESS_PROTECTED,
        ]);

        // we only want successfully routed clicks
        $filteredClicks = ClickService::get([
            'outcome' => [
                ClickService::$SUCCESS_ROUTED,
            ],
        ])->toArray();

        $this->assertCount(5, $filteredClicks['results']);

        $this->assertArrayHasKey('location', $filteredClicks['results'][0]);
        $this->assertArrayHasKey('outcome', $filteredClicks['results'][0]);
        $this->assertArrayHasKey('short_url', $filteredClicks['results'][0]);
        $this->assertArrayHasKey('tracing', $filteredClicks['results'][0]['short_url']);

        $this->assertTrue(ClickService::$SUCCESS_ROUTED == $filteredClicks['results'][0]['outcome']['id']);

        $this->assertDatabaseHas(
            'short_url_clicks',
            [
                'short_url_id' => $filteredClicks['results'][0]['short_url']['id'],
                'location_id' => $filteredClicks['results'][0]['location']['id'],
                'outcome_id' => ClickService::$SUCCESS_ROUTED,
            ]
        );
    }

    /**
     * @test
     *
     * @group Feature
     *
     * @throws UrlRepositoryException
     * @throws Throwable
     */
    public function it_can_successfully_routed_clicks_while_filtering_for_utm_source()
    {
        // 3 successful routed
        ShortUrlClick::factory()->count(5)->create([
            'short_url_id' => UrlService::findByIdentifier($this->identifier)->id,
            'outcome_id' => ClickService::$SUCCESS_ROUTED,
        ]);

        // 5 successful protected
        ShortUrlClick::factory()->count(5)->create([
            'short_url_id' => UrlService::findByIdentifier($this->identifier)->id,
            'outcome_id' => ClickService::$SUCCESS_PROTECTED,
        ]);

        // we only want successfully routed clicks
        $filteredClicks = ClickService::get([
            'outcome' => [
                ClickService::$SUCCESS_ROUTED,
            ],
            'utm_source' => [
                'testing',
            ],
        ])->toArray();

        $this->assertCount(5, $filteredClicks['results']);

        $this->assertArrayHasKey('location', $filteredClicks['results'][0]);
        $this->assertArrayHasKey('outcome', $filteredClicks['results'][0]);
        $this->assertArrayHasKey('short_url', $filteredClicks['results'][0]);
        $this->assertArrayHasKey('tracing', $filteredClicks['results'][0]['short_url']);

        $this->assertTrue(ClickService::$SUCCESS_ROUTED == $filteredClicks['results'][0]['outcome']['id']);

        $this->assertDatabaseHas(
            'short_url_clicks',
            [
                'short_url_id' => $filteredClicks['results'][0]['short_url']['id'],
                'location_id' => $filteredClicks['results'][0]['location']['id'],
                'outcome_id' => ClickService::$SUCCESS_ROUTED,
            ]
        );
    }
}

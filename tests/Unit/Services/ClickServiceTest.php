<?php

namespace YorCreative\UrlShortener\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Throwable;
use YorCreative\UrlShortener\Exceptions\FilterClicksStrategyException;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Repositories\LocationRepository;
use YorCreative\UrlShortener\Services\ClickService;
use YorCreative\UrlShortener\Tests\TestCase;

class ClickServiceTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     *
     * @group ClickService
     */
    public function it_can_can_track_a_click()
    {
        $ip = '0.0.0.0';

        ClickService::track(
            $this->identifier,
            $ip,
            ClickService::$SUCCESS_ROUTED,
            true
        );

        $this->assertDatabaseHas(
            'short_url_locations',
            [
                'ip' => $ip,
            ]
        );

        $this->assertDatabaseHas(
            'short_url_clicks',
            [
                'short_url_id' => $this->shortUrl->id,
                'location_id' => LocationRepository::findIp($ip)->id,
            ]
        );
    }

    /**
     * @test
     *
     * @group ClickService
     *
     * @throws UrlRepositoryException
     * @throws Throwable
     * @throws FilterClicksStrategyException
     */
    public function it_can_get_basic_scoped_clicks_for_short_url()
    {
        $ip = '0.0.0.0';

        ClickService::track(
            $this->identifier,
            $ip,
            ClickService::$SUCCESS_ROUTED,
            true
        );

        $clicks = ClickService::get([
            'identifiers' => [
                $this->identifier,
            ],
            'outcome' => [
                ClickService::$SUCCESS_ROUTED,
            ],
        ]);

        $this->assertCount(1, $clicks['results']);

        $click = $clicks['results']->first()->toArray();

        $this->assertArrayHasKey('id', $click);
        $this->assertArrayHasKey('location', $click);
        $this->assertArrayHasKey('outcome', $click);
        $this->assertArrayHasKey('created_at', $click);
        $this->assertArrayHasKey('short_url', $click);
        $this->assertArrayHasKey('tracing', $click['short_url']);
    }
}

<?php

namespace YorCreative\UrlShortener\Tests\Unit\Services;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Throwable;
use YorCreative\UrlShortener\Exceptions\FilterClicksStrategyException;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Models\ShortUrlClick;
use YorCreative\UrlShortener\Repositories\LocationRepository;
use YorCreative\UrlShortener\Services\ClickService;
use YorCreative\UrlShortener\Tests\TestCase;

class ClickServiceTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    #[Group('ClickService')]
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
     * @throws UrlRepositoryException
     * @throws Throwable
     * @throws FilterClicksStrategyException
     */
    #[Test]
    #[Group('ClickService')]
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

    /**
     * @throws Throwable
     */
    #[Test]
    #[Group('ClickService')]
    public function it_can_filter_failure_activation_clicks()
    {
        $shortUrl = $this->createClickedShortUrl(null, ClickService::$FAILURE_ACTIVATION);

        $clicks = ClickService::get([
            'outcome' => [
                ClickService::$FAILURE_ACTIVATION,
            ],
        ]);

        $this->assertCount(1, $clicks['results']);
        $this->assertSame($shortUrl->identifier, $clicks['results']->first()->shortUrl->identifier);
        $this->assertSame(ClickService::$FAILURE_ACTIVATION, $clicks['results']->first()->outcome->id);
    }

    /**
     * @throws Throwable
     */
    #[Test]
    #[Group('ClickService')]
    public function it_treats_non_expiring_urls_as_active()
    {
        $shortUrl = $this->createClickedShortUrl(null);

        $clicks = ClickService::get([
            'status' => [
                'active',
            ],
        ]);

        $this->assertTrue(
            $clicks['results']->contains(fn ($click) => $click->shortUrl->identifier === $shortUrl->identifier)
        );
    }

    /**
     * @throws Throwable
     */
    #[Test]
    #[Group('ClickService')]
    public function it_treats_future_expiration_urls_as_active()
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        try {
            $shortUrl = $this->createClickedShortUrl(now()->addHour()->timestamp);

            $clicks = ClickService::get([
                'status' => [
                    'active',
                ],
            ]);

            $this->assertTrue(
                $clicks['results']->contains(fn ($click) => $click->shortUrl->identifier === $shortUrl->identifier)
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @throws Throwable
     */
    #[Test]
    #[Group('ClickService')]
    public function it_filters_expired_urls()
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        try {
            $expired = $this->createClickedShortUrl(now()->subHour()->timestamp);
            $active = $this->createClickedShortUrl(now()->addHour()->timestamp);

            $clicks = ClickService::get([
                'status' => [
                    'expired',
                ],
            ]);

            $this->assertTrue(
                $clicks['results']->contains(fn ($click) => $click->shortUrl->identifier === $expired->identifier)
            );
            $this->assertFalse(
                $clicks['results']->contains(fn ($click) => $click->shortUrl->identifier === $active->identifier)
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @throws Throwable
     */
    #[Test]
    #[Group('ClickService')]
    public function it_filters_expiring_urls()
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        try {
            $expiring = $this->createClickedShortUrl(now()->addMinutes(10)->timestamp);
            $later = $this->createClickedShortUrl(now()->addHour()->timestamp);

            $clicks = ClickService::get([
                'status' => [
                    'expiring',
                ],
            ]);

            $this->assertTrue(
                $clicks['results']->contains(fn ($click) => $click->shortUrl->identifier === $expiring->identifier)
            );
            $this->assertFalse(
                $clicks['results']->contains(fn ($click) => $click->shortUrl->identifier === $later->identifier)
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @throws Throwable
     */
    #[Test]
    #[Group('ClickService')]
    public function it_treats_a_url_expiring_exactly_now_as_expired()
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        try {
            // A URL whose expiration is exactly the current second is expired,
            // matching the redirect flow (now >= expiration).
            $boundary = $this->createClickedShortUrl(now()->timestamp);

            $expired = ClickService::get(['status' => ['expired']]);
            $active = ClickService::get(['status' => ['active']]);

            $this->assertTrue(
                $expired['results']->contains(fn ($click) => $click->shortUrl->identifier === $boundary->identifier)
            );
            $this->assertFalse(
                $active['results']->contains(fn ($click) => $click->shortUrl->identifier === $boundary->identifier)
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    protected function createClickedShortUrl(?int $expiration, int $outcomeId = 1): ShortUrl
    {
        $shortUrl = ShortUrl::factory()->create([
            'expiration' => $expiration,
        ]);

        ShortUrlClick::factory()->create([
            'short_url_id' => $shortUrl->id,
            'outcome_id' => $outcomeId,
        ]);

        return $shortUrl;
    }
}

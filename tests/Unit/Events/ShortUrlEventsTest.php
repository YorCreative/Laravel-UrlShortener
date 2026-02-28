<?php

namespace YorCreative\UrlShortener\Tests\Unit\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Events\ShortUrlClicked;
use YorCreative\UrlShortener\Events\ShortUrlCreated;
use YorCreative\UrlShortener\Events\ShortUrlExpired;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Models\ShortUrlLocation;
use YorCreative\UrlShortener\Services\ClickService;
use YorCreative\UrlShortener\Tests\TestCase;

class ShortUrlEventsTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     *
     * @group Events
     */
    public function it_dispatches_short_url_created_event_on_build()
    {
        Event::fake([ShortUrlCreated::class]);

        $plainText = 'https://example.com/event-created/'.rand(999, 999999);

        UrlBuilder::shorten($plainText)->build();

        Event::assertDispatched(ShortUrlCreated::class, function (ShortUrlCreated $event) use ($plainText) {
            return $event->shortUrl->plain_text === $plainText;
        });
    }

    /**
     * @test
     *
     * @group Events
     */
    public function it_dispatches_short_url_clicked_event_on_track()
    {
        Event::fake([ShortUrlClicked::class]);

        ShortUrlLocation::firstOrCreate(['ip' => '127.0.0.1']);

        ClickService::track(
            $this->identifier,
            '127.0.0.1',
            ClickService::$SUCCESS_ROUTED,
            true
        );

        Event::assertDispatched(ShortUrlClicked::class, function (ShortUrlClicked $event) {
            return $event->identifier === $this->identifier
                && $event->outcomeId === ClickService::$SUCCESS_ROUTED;
        });
    }

    /**
     * @test
     *
     * @group Events
     */
    public function it_dispatches_short_url_expired_event_on_expired_redirect()
    {
        Event::fake([ShortUrlExpired::class]);

        $plainText = 'https://example.com/event-expired/'.rand(999, 999999);

        $url = UrlBuilder::shorten($plainText)->build();

        $identifier = $this->extractIdentifier($url);

        // Make URL expired by updating directly
        ShortUrl::where('identifier', $identifier)
            ->update(['expiration' => Carbon::now()->subHour()->timestamp]);

        ShortUrlLocation::firstOrCreate(['ip' => '127.0.0.1']);

        $prefix = config('urlshortener.branding.prefix') ?? 'v1';

        $this->get($prefix.'/'.$identifier);

        Event::assertDispatched(ShortUrlExpired::class, function (ShortUrlExpired $event) use ($identifier) {
            return $event->identifier === $identifier;
        });
    }

    protected function extractIdentifier(string $url): string
    {
        $url = rtrim($url, '/');
        $parts = explode('/', $url);

        return end($parts);
    }
}

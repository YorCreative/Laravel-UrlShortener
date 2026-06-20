<?php

namespace YorCreative\UrlShortener\Tests\Unit\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Exceptions\UrlBuilderException;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Exceptions\UrlServiceException;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Services\UrlService;
use YorCreative\UrlShortener\Tests\Models\DemoOwner;
use YorCreative\UrlShortener\Tests\TestCase;

class UrlServiceTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @throws UrlRepositoryException
     */
    #[Test]
    #[Group('UrlService')]
    public function it_can_find_a_short_url_by_utm_combination()
    {
        // extra url to filter through
        UrlService::shorten('https://testing.com/something/so/long/i/need/a/short/url'.rand(999, 999999))
            ->withTracing([
                'utm_campaign' => 'alpha',
                'utm_source' => 'alpha',
                'utm_medium' => 'testing',
            ])
            ->build();

        // url to find
        $targetPlainText = 'https://testing.com/something/so/long/i/need/a/short/url'.rand(999, 999999);
        $targetUtmCombination = [
            'utm_campaign' => 'alpha',
            'utm_source' => 'bravo',
            'utm_medium' => 'testing',
        ];

        UrlService::shorten($targetPlainText)
            ->withTracing($targetUtmCombination)
            ->build();

        // extra url to filter through
        UrlService::shorten('https://testing.com/something/so/long/i/need/a/short/url'.rand(999, 999999))
            ->withTracing([
                'utm_campaign' => 'alpha',
                'utm_source' => 'charlie',
                'utm_medium' => 'testing',
            ])
            ->build();

        $this->assertDatabaseHas('short_url_tracings', [
            'utm_campaign' => 'alpha',
            'utm_source' => 'bravo',
            'utm_medium' => 'testing',
        ]);

        $shortUrls = UrlService::findByUtmCombination($targetUtmCombination);

        $this->assertEquals($targetUtmCombination['utm_source'], $shortUrls->toArray()[0]['tracing']['utm_source']);
        $this->assertEquals($targetUtmCombination['utm_campaign'], $shortUrls->toArray()[0]['tracing']['utm_campaign']);
        $this->assertEquals($targetUtmCombination['utm_medium'], $shortUrls->toArray()[0]['tracing']['utm_medium']);
    }

    #[Test]
    #[Group('UrlService')]
    public function it_can_can_find_short_url_by_the_hash()
    {
        $shortUrl = UrlService::findByHash($this->hashed);

        $this->assertTrue($this->identifier == $shortUrl->identifier);
        $this->assertTrue($this->plain_text == $shortUrl->plain_text);
    }

    #[Test]
    #[Group('UrlService')]
    public function it_can_can_find_short_url_by_the_plain_text()
    {
        $shortUrl = UrlService::findByPlainText($this->plain_text);

        $this->assertTrue($this->hashed == $shortUrl->hashed);
        $this->assertTrue($this->identifier == $shortUrl->identifier);
    }

    #[Test]
    #[Group('UrlService')]
    public function it_can_can_find_short_url_by_the_identifier()
    {
        $shortUrl = UrlService::findByIdentifier($this->identifier);

        $this->assertTrue($this->hashed == $shortUrl->hashed);
        $this->assertTrue($this->plain_text == $shortUrl->plain_text);
    }

    /**
     * @throws UrlBuilderException
     * @throws UrlRepositoryException
     * @throws UrlServiceException
     */
    #[Test]
    #[Group('UrlService')]
    public function it_can_successfully_attempt_to_verify_password()
    {
        $plain_text = 'https://something.com/really-long'.rand(5, 9999);

        $url = UrlBuilder::shorten($plain_text)
            ->withPassword('password')
            ->build();

        $identifier = str_replace($this->base, '', $url);

        $shortUrl = UrlService::attempt($identifier, 'password');

        $this->assertTrue($plain_text == $shortUrl->plain_text);
    }

    /**
     * @throws UrlBuilderException
     * @throws UrlRepositoryException
     * @throws UrlServiceException
     */
    #[Test]
    #[Group('UrlService')]
    public function it_can_successfully_attempt_to_verify_password_and_fail()
    {
        $plain_text = 'https://something.com/really-long'.rand(5, 9999);

        $url = UrlBuilder::shorten($plain_text)
            ->withPassword('password')
            ->build();

        $identifier = str_replace($this->base, '', $url);

        $this->assertNull(UrlService::attempt($identifier, 'not_password'));
    }

    /**
     * @throws UrlRepositoryException
     */
    #[Test]
    #[Group('UrlService')]
    public function it_can_attach_ownership_to_short_url()
    {
        $owner = DemoOwner::factory()->create();

        $this->assertNull($this->shortUrl->ownership);

        $primary_key = $owner->getKeyName();

        $ownership = [
            'ownerable_type' => $owner->getMorphClass(),
            'ownerable_id' => $owner->$primary_key,
        ];

        UrlService::attachOwnership($this->identifier, $ownership['ownerable_type'], $ownership['ownerable_id']);

        $this->assertDatabaseHas(
            'short_url_ownerships',
            array_merge($ownership, [
                'short_url_id' => $this->shortUrl->id,
            ])
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('UrlService')]
    public function it_can_set_an_activation_time_successfully()
    {
        UrlService::shorten('https://something.com/activation-test')
            ->withActivation(Carbon::now()->addMinute()->timestamp)
            ->build();

        $this->assertTrue(ShortUrl::where('plain_text', 'https://something.com/activation-test')->first()->hasActivation());
    }

    #[Test]
    #[Group('UrlService')]
    public function it_treats_zero_open_limit_as_unlimited()
    {
        $plainText = 'https://something.com/no-limit/'.rand(999, 999999);
        $url = UrlService::shorten($plainText)
            ->withOpenLimit(0)
            ->build();

        $identifier = str_replace($this->base, '', $url);
        $shortUrl = UrlService::findByIdentifier($identifier);

        $this->assertNull($shortUrl->limit);
    }

    #[Test]
    #[Group('UrlService')]
    public function it_rejects_negative_open_limit()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('cannot be negative');

        UrlService::shorten('https://something.com/bad-limit/'.rand(999, 999999))
            ->withOpenLimit(-1);
    }

    #[Test]
    #[Group('UrlService')]
    public function it_can_find_or_create_returns_existing_short_url()
    {
        $plain_text = 'https://testing.com/findorcreate/existing/'.rand(999, 999999);

        // First, create a short URL
        UrlService::shorten($plain_text)->build();

        // Now use findOrCreate - should return the existing ShortUrl model
        $result = UrlService::findOrCreate($plain_text);

        $this->assertInstanceOf(ShortUrl::class, $result);
        $this->assertEquals($plain_text, $result->plain_text);
    }

    #[Test]
    #[Group('UrlService')]
    public function it_can_find_or_create_returns_builder_for_new_url()
    {
        $plain_text = 'https://testing.com/findorcreate/new/'.rand(999, 999999);

        // Use findOrCreate for a URL that doesn't exist - should return UrlBuilder
        $result = UrlService::findOrCreate($plain_text);

        $this->assertInstanceOf(UrlBuilder::class, $result);
    }

    #[Test]
    #[Group('UrlService')]
    public function it_throws_exception_when_finding_nonexistent_identifier()
    {
        $this->expectException(UrlRepositoryException::class);

        UrlService::findByIdentifier('nonexistent_identifier_'.rand(999, 999999));
    }
}

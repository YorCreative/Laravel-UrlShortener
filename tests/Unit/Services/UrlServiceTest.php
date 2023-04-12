<?php

namespace YorCreative\UrlShortener\Tests\Unit\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
     * @test
     *
     * @group UrlService
     *
     * @throws UrlRepositoryException
     */
    public function it_can_find_a_short_url_by_utm_combination()
    {
        // extra url to filter through
        UrlService::shorten('testing.com/something/so/long/i/need/a/short/url'.rand(999, 999999))
            ->withTracing([
                'utm_campaign' => 'alpha',
                'utm_source' => 'alpha',
                'utm_medium' => 'testing',
            ])
            ->build();

        // url to find
        $targetPlainText = 'testing.com/something/so/long/i/need/a/short/url'.rand(999, 999999);
        $targetUtmCombination = [
            'utm_campaign' => 'alpha',
            'utm_source' => 'bravo',
            'utm_medium' => 'testing',
        ];

        UrlService::shorten($targetPlainText)
            ->withTracing($targetUtmCombination)
            ->build();

        // extra url to filter through
        UrlService::shorten('testing.com/something/so/long/i/need/a/short/url'.rand(999, 999999))
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

    /**
     * @test
     *
     * @group UrlService
     */
    public function it_can_can_find_short_url_by_the_hash()
    {
        $shortUrl = UrlService::findByHash($this->hashed);

        $this->assertTrue($this->identifier == $shortUrl->identifier);
        $this->assertTrue($this->plain_text == $shortUrl->plain_text);
    }

    /**
     * @test
     *
     * @group UrlService
     */
    public function it_can_can_find_short_url_by_the_plain_text()
    {
        $shortUrl = UrlService::findByPlainText($this->plain_text);

        $this->assertTrue($this->hashed == $shortUrl->hashed);
        $this->assertTrue($this->identifier == $shortUrl->identifier);
    }

    /**
     * @test
     *
     * @group UrlService
     */
    public function it_can_can_find_short_url_by_the_identifier()
    {
        $shortUrl = UrlService::findByIdentifier($this->identifier);

        $this->assertTrue($this->hashed == $shortUrl->hashed);
        $this->assertTrue($this->plain_text == $shortUrl->plain_text);
    }

    /**
     * @test
     *
     * @group UrlService
     *
     * @throws UrlBuilderException
     * @throws UrlRepositoryException
     * @throws UrlServiceException
     */
    public function it_can_successfully_attempt_to_verify_password()
    {
        $plain_text = 'something.com/really-long'.rand(5, 9999);

        $url = UrlBuilder::shorten($plain_text)
            ->withPassword('password')
            ->build();

        $identifier = str_replace($this->base, '', $url);

        $shortUrl = UrlService::attempt($identifier, 'password');

        $this->assertTrue($plain_text == $shortUrl->plain_text);
    }

    /**
     * @test
     *
     * @group UrlService
     *
     * @throws UrlBuilderException
     * @throws UrlRepositoryException
     * @throws UrlServiceException
     */
    public function it_can_successfully_attempt_to_verify_password_and_fail()
    {
        $plain_text = 'something.com/really-long'.rand(5, 9999);

        $url = UrlBuilder::shorten($plain_text)
            ->withPassword('password')
            ->build();

        $identifier = str_replace($this->base, '', $url);

        $this->assertNull(UrlService::attempt($identifier, 'not_password'));
    }

    /**
     * @test
     *
     * @group UrlService
     *
     * @throws UrlRepositoryException
     */
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
     * @test
     *
     * @group UrlService
     *
     * @throws Exception
     */
    public function it_can_set_an_activation_time_successfully()
    {
        UrlService::shorten('something')
            ->withActivation(Carbon::now()->addMinute()->timestamp)
            ->build();

        $this->assertTrue(ShortUrl::where('plain_text', 'something')->first()->hasActivation());
    }
}

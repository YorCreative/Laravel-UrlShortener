<?php

namespace YorCreative\UrlShortener\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Exceptions\ClickRepositoryException;
use YorCreative\UrlShortener\Models\ShortUrlClick;
use YorCreative\UrlShortener\Repositories\ClickRepository;
use YorCreative\UrlShortener\Services\ClickService;
use YorCreative\UrlShortener\Tests\TestCase;

class ClickRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     *
     * @group ClickRepository
     */
    public function it_can_find_a_click_by_its_id()
    {
        ClickService::track(
            $this->identifier,
            '0.0.0.0',
            ClickService::$SUCCESS_ROUTED,
            true
        );

        $this->assertEquals(
            '0.0.0.0',
            ClickRepository::findById(1)->toArray()['location']['ip']
        );
    }

    /**
     * @test
     *
     * @group ClickRepository
     */
    public function it_can_create_a_click_in_db()
    {
        ClickRepository::createClick(
            $this->shortUrl->id,
            1,
            ClickService::$FAILURE_ACTIVATION
        );

        $this->assertDatabaseHas(
            'short_url_clicks',
            [
                'short_url_id' => $this->shortUrl->id,
                'location_id' => 1,
                'outcome_id' => ClickService::$FAILURE_ACTIVATION,
            ]
        );
    }

    /**
     * @test
     *
     * @group ClickRepository
     */
    public function it_can_get_correct_with_default_relations()
    {
        $this->assertEquals([
            'location', 'outcome', 'shortUrl.tracing',
        ], ClickRepository::defaultWithRelations());
    }

    /**
     * @test
     *
     * @group ClickRepository
     */
    public function it_can_find_click_for_domain_when_multidomain_disabled()
    {
        config(['urlshortener.domains.enabled' => false]);

        ClickService::track(
            $this->identifier,
            '0.0.0.0',
            ClickService::$SUCCESS_ROUTED,
            true
        );

        $click = ShortUrlClick::latest()->first();

        // Should find click regardless of domain when multi-domain is disabled
        $foundClick = ClickRepository::findByIdForDomain($click->id, 'any-domain.com');

        $this->assertEquals($click->id, $foundClick->id);
    }

    /**
     * @test
     *
     * @group ClickRepository
     */
    public function it_filters_click_by_domain_when_multidomain_enabled()
    {
        config(['urlshortener.domains.enabled' => true]);
        config(['urlshortener.domains.hosts' => [
            'domain1.io' => ['prefix' => 'd1'],
            'domain2.io' => ['prefix' => 'd2'],
        ]]);

        // Create URL on domain1
        $plainText1 = 'https://domain1-click.com/'.rand(999, 999999);
        $url1 = UrlBuilder::shorten($plainText1)
            ->forDomain('domain1.io')
            ->build();

        $parts1 = explode('/', rtrim($url1, '/'));
        $identifier1 = end($parts1);

        // Create click for domain1 URL
        ClickService::track($identifier1, '127.0.0.1', ClickService::$SUCCESS_ROUTED, true, 'domain1.io');

        $click = ShortUrlClick::latest()->first();

        // Should find click with correct domain
        $foundClick = ClickRepository::findByIdForDomain($click->id, 'domain1.io');
        $this->assertEquals($click->id, $foundClick->id);

        // Should NOT find click with wrong domain
        $this->expectException(ClickRepositoryException::class);
        ClickRepository::findByIdForDomain($click->id, 'domain2.io');
    }

    /**
     * @test
     *
     * @group ClickRepository
     */
    public function it_throws_exception_for_nonexistent_click_id()
    {
        $this->expectException(ClickRepositoryException::class);

        ClickRepository::findById(999999);
    }
}

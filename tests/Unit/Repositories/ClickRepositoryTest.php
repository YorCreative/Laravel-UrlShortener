<?php

namespace YorCreative\UrlShortener\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
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
        Config::set('location.testing.enabled', true);

        ClickService::track(
            $this->identifier,
            '0.0.0.0',
            ClickService::$SUCCESS_ROUTED
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
}

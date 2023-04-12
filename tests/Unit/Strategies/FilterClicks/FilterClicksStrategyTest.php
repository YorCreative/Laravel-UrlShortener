<?php

namespace YorCreative\UrlShortener\Tests\Unit\Services;

use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Strategies\FilterClicks\FilterClicksStrategy;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\BatchFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\IdentifierFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\OutcomeFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\OwnershipFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\StatusFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\TracingCampaignFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\TracingContentFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\TracingIdFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\TracingMediumFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\TracingSourceFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\TracingTermFilter;
use YorCreative\UrlShortener\Tests\Models\DemoOwner;
use YorCreative\UrlShortener\Tests\TestCase;

class FilterClicksStrategyTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->getFilters = new Collection([
            new OutcomeFilter(),
            new BatchFilter(),
            new IdentifierFilter(),
            new StatusFilter(),
            new OwnershipFilter(),
            new TracingIdFilter(),
        ]);
    }

    /**
     * @test
     *
     * @group FilterClickStrategy
     */
    public function it_can_add_filter_strategies()
    {
        $filterStrategy = new FilterClicksStrategy();

        $this->getFilters->each(function ($filter) use ($filterStrategy) {
            $filterStrategy->addFilter($filter);
        });

        $this->assertCount(6, $filterStrategy->getStrategies());
    }

    /**
     * @test
     *
     * @group FilterClickStrategy
     */
    public function it_can_can_process_outcome_filter()
    {
        $filterQuery = [
            'outcome' => [
                1,
            ],
        ];

        $this->assertTrue((new OutcomeFilter())->canProcess($filterQuery));
    }

    /**
     * @test
     *
     * @group FilterClickStrategy
     */
    public function it_can_can_process_batch_filter()
    {
        $filterQuery = [
            'limit' => 100,
            'offset' => 1500,
        ];

        $this->assertTrue((new BatchFilter())->canProcess($filterQuery));
    }

    /**
     * @test
     *
     * @group FilterClickStrategy
     */
    public function it_can_can_process_identifier_filter()
    {
        $filterQuery = [
            'identifiers' => [
                'xyz',
            ],
        ];

        $this->assertTrue((new IdentifierFilter())->canProcess($filterQuery));
    }

    /**
     * @test
     *
     * @group FilterClickStrategy
     */
    public function it_can_can_process_status_filter()
    {
        $filterQuery = [
            'status' => [
                'active',
            ],
        ];

        $this->assertTrue((new StatusFilter())->canProcess($filterQuery));
    }

    /**
     * @test
     *
     * @group FilterClickStrategy
     */
    public function it_can_can_process_ownership_filter()
    {
        $filterQuery = [
            'ownership' => [
                (new DemoOwner()),
            ],
        ];

        $this->assertTrue((new OwnershipFilter())->canProcess($filterQuery));
    }

    /**
     * @test
     *
     * @group FilterClickStrategy
     */
    public function it_can_can_process_tracing_utm_id_filter()
    {
        $filterQuery = [
            'utm_id' => [
                '1234',
                '4321',
            ],
        ];

        $this->assertTrue((new TracingIdFilter())->canProcess($filterQuery));
    }

    /**
     * @test
     *
     * @group FilterClickStrategy
     */
    public function it_can_can_process_tracing_utm_campaign_filter()
    {
        $filterQuery = [
            'utm_campaign' => [
                'something',
                'something',
            ],
        ];

        $this->assertTrue((new TracingCampaignFilter())->canProcess($filterQuery));
    }

    /**
     * @test
     *
     * @group FilterClickStrategy
     */
    public function it_can_can_process_tracing_utm_source_filter()
    {
        $filterQuery = [
            'utm_source' => [
                'something',
                'something',
            ],
        ];

        $this->assertTrue((new TracingSourceFilter())->canProcess($filterQuery));
    }

    /**
     * @test
     *
     * @group FilterClickStrategy
     */
    public function it_can_can_process_tracing_utm_medium_filter()
    {
        $filterQuery = [
            'utm_medium' => [
                'something',
                'something',
            ],
        ];

        $this->assertTrue((new TracingMediumFilter())->canProcess($filterQuery));
    }

    /**
     * @test
     *
     * @group FilterClickStrategy
     */
    public function it_can_can_process_tracing_utm_content_filter()
    {
        $filterQuery = [
            'utm_content' => [
                'something',
                'something',
            ],
        ];

        $this->assertTrue((new TracingContentFilter())->canProcess($filterQuery));
    }

    /**
     * @test
     *
     * @group FilterClickStrategy
     */
    public function it_can_can_process_tracing_utm_term_filter()
    {
        $filterQuery = [
            'utm_term' => [
                'something',
                'something',
            ],
        ];

        $this->assertTrue((new TracingTermFilter())->canProcess($filterQuery));
    }
}

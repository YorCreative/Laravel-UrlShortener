<?php

namespace YorCreative\UrlShortener\Tests\Unit\Services;

use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Strategies\FilterClicks\FilterClicksStrategy;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\BatchFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\IdentifierFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\OutcomeFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\OwnershipFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\StatusFilter;
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
        ]);
    }

    /**
     * @test
     * @group FilterClickStrategy
     */
    public function it_can_add_filter_strategies()
    {
        $filterStrategy = new FilterClicksStrategy();

        $this->getFilters->each(function ($filter) use ($filterStrategy) {
            $filterStrategy->addFilter($filter);
        });

        $this->assertCount(5, $filterStrategy->getStrategies());
    }

    /**
     * @test
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
}

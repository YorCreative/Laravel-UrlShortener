<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks;

use Exception;
use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;
use YorCreative\UrlShortener\Exceptions\FilterClicksStrategyException;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\AbstractFilter;

class FilterClicksStrategy
{
    protected Collection $strategies;

    public function __construct()
    {
        $this->strategies = new Collection();
    }

    /**
     * @throws FilterClicksStrategyException
     */
    public function addFilter(AbstractFilter $filter): void
    {
        try {
            $this->strategies->add($filter);
        } catch (Exception $exception) {
            throw new FilterClicksStrategyException($exception);
        }
    }

    public function handle(ClickQueryBuilder &$clickQueryBuilder): void
    {
        $this->getStrategies()->each(function ($strategy) use (&$clickQueryBuilder) {
            $strategy->handle($clickQueryBuilder);
        });
    }

    public function getStrategies(): Collection
    {
        return $this->strategies;
    }
}

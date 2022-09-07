<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;
use YorCreative\UrlShortener\Repositories\TracingRepository;

class TracingTermFilter extends AbstractFilter
{
    /**
     * @param  array  $filter
     * @return bool
     */
    public function canProcess(array $filter): bool
    {
        $this->filter = $filter;

        return isset($filter[TracingRepository::$TERM])
            && is_array($filter[TracingRepository::$TERM]);
    }

    /**
     * @return array
     */
    public function getAvailableFilterOptions(): array
    {
        return [];
    }

    /**
     * @param  ClickQueryBuilder  $clickQueryBuilder
     */
    public function handle(ClickQueryBuilder &$clickQueryBuilder): void
    {
        $clickQueryBuilder->whereInTracingTerm($this->filter[TracingRepository::$TERM]);
    }
}

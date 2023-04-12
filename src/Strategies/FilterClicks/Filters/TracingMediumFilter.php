<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;
use YorCreative\UrlShortener\Repositories\TracingRepository;

class TracingMediumFilter extends AbstractFilter
{
    public function canProcess(array $filter): bool
    {
        $this->filter = $filter;

        return isset($filter[TracingRepository::$MEDIUM])
            && is_array($filter[TracingRepository::$MEDIUM]);
    }

    public function getAvailableFilterOptions(): array
    {
        return [];
    }

    public function handle(ClickQueryBuilder &$clickQueryBuilder): void
    {
        $clickQueryBuilder->whereInTracingMedium($this->filter[TracingRepository::$MEDIUM]);
    }
}

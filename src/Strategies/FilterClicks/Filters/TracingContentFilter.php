<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;
use YorCreative\UrlShortener\Repositories\TracingRepository;

class TracingContentFilter extends AbstractFilter
{
    public function canProcess(array $filter): bool
    {
        $this->filter = $filter;

        return isset($filter[TracingRepository::$CONTENT])
            && is_array($filter[TracingRepository::$CONTENT]);
    }

    public function getAvailableFilterOptions(): array
    {
        return [];
    }

    public function handle(ClickQueryBuilder &$clickQueryBuilder): void
    {
        $clickQueryBuilder->whereInTracingContent($this->filter[TracingRepository::$CONTENT]);
    }
}

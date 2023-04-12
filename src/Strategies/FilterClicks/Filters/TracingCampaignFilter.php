<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;
use YorCreative\UrlShortener\Repositories\TracingRepository;

class TracingCampaignFilter extends AbstractFilter
{
    public function canProcess(array $filter): bool
    {
        $this->filter = $filter;

        return isset($filter[TracingRepository::$CAMPAIGN])
            && is_array($filter[TracingRepository::$CAMPAIGN]);
    }

    public function getAvailableFilterOptions(): array
    {
        return [];
    }

    public function handle(ClickQueryBuilder &$clickQueryBuilder): void
    {
        $clickQueryBuilder->whereInTracingCampaign($this->filter[TracingRepository::$CAMPAIGN]);
    }
}

<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;
use YorCreative\UrlShortener\Exceptions\FilterClicksStrategyException;

class OwnershipFilter extends AbstractFilter
{
    public function canProcess(array $filter): bool
    {
        $this->filter = $filter;

        return isset($filter['ownership']) && is_array($filter['ownership']);
    }

    public function getAvailableFilterOptions(): array
    {
        return [];
    }

    /**
     * @throws FilterClicksStrategyException
     */
    public function handle(ClickQueryBuilder &$clickQueryBuilder): void
    {
        $ownership = $this->filter['ownership'];

        $clickQueryBuilder->whereOwnership($ownership['ownerable_type'], $ownership['ownerable_id']);
    }
}

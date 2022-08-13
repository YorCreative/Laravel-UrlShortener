<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;

class IdentifierFilter extends AbstractFilter
{
    /**
     * @param  array  $filter
     * @return bool
     */
    public function canProcess(array $filter): bool
    {
        $this->filter = $filter;

        return isset($filter['identifiers']) && is_array($filter['identifiers']);
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
        $clickQueryBuilder->whereInIdentifiers($this->filter['identifiers']);
    }
}

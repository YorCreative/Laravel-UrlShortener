<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;

class IdentifierFilter extends AbstractFilter
{
    public function canProcess(array $filter): bool
    {
        $this->filter = $filter;

        return isset($filter['identifiers']) && is_array($filter['identifiers']);
    }

    public function getAvailableFilterOptions(): array
    {
        return [];
    }

    public function handle(ClickQueryBuilder &$clickQueryBuilder): void
    {
        $clickQueryBuilder->whereInIdentifiers($this->filter['identifiers']);
    }
}

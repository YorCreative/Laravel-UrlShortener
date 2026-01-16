<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;

class BatchFilter extends AbstractFilter
{
    public function canProcess(array $filter): bool
    {
        $this->filter = $filter;

        // Process if limit or offset is provided (for pagination)
        return (isset($filter['limit']) && is_int($filter['limit']))
            || (isset($filter['offset']) && is_int($filter['offset']));
    }

    public function getAvailableFilterOptions(): array
    {
        return [];
    }

    public function handle(ClickQueryBuilder &$clickQueryBuilder): void
    {
        $offset = $this->filter['offset'] ?? 0;
        $limit = $this->filter['limit'] ?? 100;

        $clickQueryBuilder->offset(($offset > 0) ? $offset : 0);
        $clickQueryBuilder->limit(($limit > 0 && $limit <= 100) ? $limit : 100);
    }
}

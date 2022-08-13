<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;

class BatchFilter extends AbstractFilter
{
    /**
     * @param  array  $filter
     * @return bool
     */
    public function canProcess(array $filter): bool
    {
        $this->filter = $filter;

        return isset($filter['limit'])
            || ((
                isset($filter['limit'])
                && is_int($filter['limit'])
            )
            && (
                isset($filter['offset'])
                && is_int($filter['offset'])
            )
            );
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
        $clickQueryBuilder->offset(($this->filter['offset'] > 0) ? $this->filter['offset'] : 0);
        $clickQueryBuilder->limit(($this->filter['limit'] > 0 && $this->filter['limit'] <= 100) ? $this->filter['limit'] : 100);
    }
}

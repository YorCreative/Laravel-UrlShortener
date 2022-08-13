<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;
use YorCreative\UrlShortener\Services\ClickService;

class OutcomeFilter extends AbstractFilter
{
    /**
     * @param  array  $filter
     * @return bool
     */
    public function canProcess(array $filter): bool
    {
        $this->filter = $filter;

        return isset($filter['outcome'])
            && is_array($filter['outcome'])
            && $this->hasOptions($filter['outcome']);
    }

    /**
     * @return array
     */
    public function getAvailableFilterOptions(): array
    {
        return [
            ClickService::$SUCCESS_ROUTED,
            ClickService::$SUCCESS_PROTECTED,
            ClickService::$FAILURE_PROTECTED,
            ClickService::$FAILURE_LIMIT,
            ClickService::$FAILURE_EXPIRATION,
        ];
    }

    /**
     * @param  ClickQueryBuilder  $clickQueryBuilder
     */
    public function handle(ClickQueryBuilder &$clickQueryBuilder): void
    {
        $clickQueryBuilder->whereOutcome(
            $this->getOptions($this->filter['outcome'])
        );
    }
}

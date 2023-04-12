<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;
use YorCreative\UrlShortener\Services\ClickService;

class OutcomeFilter extends AbstractFilter
{
    public function canProcess(array $filter): bool
    {
        $this->filter = $filter;

        return isset($filter['outcome'])
            && is_array($filter['outcome'])
            && $this->hasOptions($filter['outcome']);
    }

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

    public function handle(ClickQueryBuilder &$clickQueryBuilder): void
    {
        $clickQueryBuilder->whereOutcome(
            $this->getOptions($this->filter['outcome'])
        );
    }
}

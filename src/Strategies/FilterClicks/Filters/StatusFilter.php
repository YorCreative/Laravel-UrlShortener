<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;

class StatusFilter extends AbstractFilter
{
    public function canProcess(array $filter): bool
    {
        $this->filter = $filter;

        return isset($filter['status'])
            && is_array($filter['status'])
            && $this->hasOptions($filter['status']);
    }

    public function getAvailableFilterOptions(): array
    {
        return [
            'active',
            'expiring',
            'expired',
        ];
    }

    public function handle(ClickQueryBuilder &$clickQueryBuilder): void
    {
        if (in_array('active', $this->filter['status'])) {
            $clickQueryBuilder->isNotExpired();
        }

        if (in_array('expired', $this->filter['status'])) {
            $clickQueryBuilder->isExpired();
        }

        if (in_array('expiring', $this->filter['status'])) {
            $clickQueryBuilder->isExpiring();
        }
    }
}

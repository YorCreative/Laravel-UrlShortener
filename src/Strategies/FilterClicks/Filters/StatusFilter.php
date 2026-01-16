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
        $statuses = array_intersect($this->filter['status'], $this->getAvailableFilterOptions());

        if (empty($statuses)) {
            return;
        }

        // If only one status, apply directly
        if (count($statuses) === 1) {
            $this->applyStatus($clickQueryBuilder, reset($statuses));

            return;
        }

        // Multiple statuses should use OR logic
        $clickQueryBuilder->where(function ($query) use ($statuses) {
            foreach ($statuses as $index => $status) {
                if ($index === 0) {
                    $this->applyStatusToQuery($query, $status);
                } else {
                    $query->orWhere(function ($q) use ($status) {
                        $this->applyStatusToQuery($q, $status);
                    });
                }
            }
        });
    }

    protected function applyStatus(ClickQueryBuilder &$clickQueryBuilder, string $status): void
    {
        match ($status) {
            'active' => $clickQueryBuilder->isNotExpired(),
            'expired' => $clickQueryBuilder->isExpired(),
            'expiring' => $clickQueryBuilder->isExpiring(),
            default => null,
        };
    }

    protected function applyStatusToQuery($query, string $status): void
    {
        $now = now()->timestamp;

        match ($status) {
            'active' => $query->whereIn('short_url_id', function ($q) use ($now) {
                $q->from('short_urls')->where('expiration', '>', $now)->select('id');
            }),
            'expired' => $query->whereIn('short_url_id', function ($q) use ($now) {
                $q->from('short_urls')->where('expiration', '<', $now)->select('id');
            }),
            'expiring' => $query->whereIn('short_url_id', function ($q) {
                $q->from('short_urls')
                    ->whereBetween('expiration', [now()->addMinute()->timestamp, now()->addMinutes(30)])
                    ->select('id');
            }),
            default => null,
        };
    }
}

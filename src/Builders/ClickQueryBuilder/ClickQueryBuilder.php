<?php

namespace YorCreative\UrlShortener\Builders\ClickQueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Repositories\ClickRepository;

class ClickQueryBuilder extends Builder
{
    public function whereInTracingTerm(array $utm_terms): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) use ($utm_terms) {
            $query->from('short_url_tracings');
            $query->whereIn('utm_term', $utm_terms);
            $query->select('short_url_id');
        });
    }

    public function whereInTracingSource(array $utm_sources): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) use ($utm_sources) {
            $query->from('short_url_tracings');
            $query->whereIn('utm_source', $utm_sources);
            $query->select('short_url_id');
        });
    }

    public function whereInTracingMedium(array $utm_mediums): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) use ($utm_mediums) {
            $query->from('short_url_tracings');
            $query->whereIn('utm_medium', $utm_mediums);
            $query->select('short_url_id');
        });
    }

    public function whereInTracingId(array $utm_ids): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) use ($utm_ids) {
            $query->from('short_url_tracings');
            $query->whereIn('utm_id', $utm_ids);
            $query->select('short_url_id');
        });
    }

    public function whereInTracingContent(array $utm_contents): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) use ($utm_contents) {
            $query->from('short_url_tracings');
            $query->whereIn('utm_content', $utm_contents);
            $query->select('short_url_id');
        });
    }

    public function whereInTracingCampaign(array $utm_campaigns): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) use ($utm_campaigns) {
            $query->from('short_url_tracings');
            $query->whereIn('utm_campaign', $utm_campaigns);
            $query->select('short_url_id');
        });
    }

    public function whereOutcome(array $outcomes): ClickQueryBuilder
    {
        return $this->whereIn('outcome_id', function ($query) use ($outcomes) {
            $query->from('short_url_outcomes');
            $query->whereIn('id', $outcomes);
            $query->select('id');
        });
    }

    public function isNotExpired(): ClickQueryBuilder
    {
        $now = now()->timestamp;

        return $this->whereIn('short_url_id', function ($query) use ($now) {
            $query->from('short_urls');
            $query->whereNull('expiration')
                ->orWhere('expiration', '>', $now);
            $query->select('id');
        });
    }

    protected function expirationFilter(string $direction, int $current_timestamp): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) use ($direction, $current_timestamp) {
            $query->from('short_urls');
            $query->where('expiration', $direction, $current_timestamp);
            $query->select('id');
        });
    }

    public function isExpiring(): ClickQueryBuilder
    {
        $now = now();
        $startsAt = $now->copy()->addMinute()->timestamp;
        $endsAt = $now->copy()->addMinutes(30)->timestamp;

        return $this->whereIn('short_url_id', function ($query) use ($startsAt, $endsAt) {
            $query->from('short_urls');
            $query->whereBetween('expiration', [$startsAt, $endsAt]);
            $query->select('id');
        });
    }

    public function isExpired(): ClickQueryBuilder
    {
        // Expired when now >= expiration, consistent with the redirect flow.
        return $this->expirationFilter('<=', now()->timestamp);
    }

    public function whereOwnership(Model $model): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) use ($model) {
            $query->from('short_url_ownerships');
            $query->where('ownerable_id', $model->id);
            $query->select('short_url_id');
        });
    }

    public function whereInIdentifiers(array $identifiers): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) use ($identifiers) {
            $query->from('short_urls');
            $query->whereIn('identifier', $identifiers);
            $query->select('id');
        });
    }

    public function build(): Collection
    {
        return $this->withRelations()->get();
    }

    public function withRelations(array $relations = []): ClickQueryBuilder
    {
        return $this->with(
            empty($relations)
                ? ClickRepository::defaultWithRelations()
                : $relations
        );
    }
}

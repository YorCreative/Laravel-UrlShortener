<?php

namespace YorCreative\UrlShortener\Builders\ClickQueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Repositories\ClickRepository;

class ClickQueryBuilder extends Builder
{
    /**
     * @param  array  $outcomes
     * @return ClickQueryBuilder
     */
    public function whereOutcome(array $outcomes): ClickQueryBuilder
    {
        return $this->whereIn('outcome_id', function ($query) use ($outcomes) {
            $query->from('short_url_outcomes');
            $query->whereIn('id', $outcomes);
            $query->select('id');
        });
    }

    /**
     * @return ClickQueryBuilder
     */
    public function isNotExpired(): ClickQueryBuilder
    {
        return $this->expirationFilter('>', now()->timestamp);
    }

    /**
     * @param  string  $direction
     * @param $current_timestamp
     * @return ClickQueryBuilder
     */
    protected function expirationFilter(string $direction, $current_timestamp): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) use ($direction, $current_timestamp) {
            $query->from('short_urls');
            $query->where('expiration', $direction, $current_timestamp);
            $query->select('id');
        });
    }

    /**
     * @return ClickQueryBuilder
     */
    public function isExpiring(): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) {
            $query->from('short_urls');
            $query->whereBetween('expiration', [now()->addMinute()->timestamp, now()->addMinutes(30)]);
            $query->select('id');
        });
    }

    /**
     * @return ClickQueryBuilder
     */
    public function isExpired(): ClickQueryBuilder
    {
        return $this->expirationFilter('<', now()->timestamp);
    }

    /**
     * @param  Model  $model
     * @return ClickQueryBuilder
     */
    public function whereOwnership(Model $model): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) use ($model) {
            $query->from('short_url_ownerships');
            $query->where('ownerable_id', $model->id);
            $query->select('short_url_id');
        });
    }

    /**
     * @param  array  $identifiers
     * @return ClickQueryBuilder
     */
    public function whereInIdentifiers(array $identifiers): ClickQueryBuilder
    {
        return $this->whereIn('short_url_id', function ($query) use ($identifiers) {
            $query->from('short_urls');
            $query->whereIn('identifier', $identifiers);
            $query->select('id');
        });
    }

    /**
     * @return Collection
     */
    public function build(): Collection
    {
        return $this->withRelations()->get();
    }

    /**
     * @param  array  $relations
     * @return ClickQueryBuilder
     */
    public function withRelations(array $relations = []): ClickQueryBuilder
    {
        return $this->with(
            empty($relations)
                ? ClickRepository::defaultWithRelations()
                : $relations
        );
    }
}

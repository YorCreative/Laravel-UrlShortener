<?php

namespace YorCreative\UrlShortener\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;
use YorCreative\UrlShortener\Traits\PublishableHasFactory;

class ShortUrlClick extends Model
{
    use PublishableHasFactory, SoftDeletes;

    /**
     * @var bool
     */
    public $incrementing = true;

    /**
     * @var string
     */
    protected $table = 'short_url_clicks';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string[]
     */
    protected $fillable = [
        'short_url_id',
        'location_id',
        'outcome_id',
        'headers',
        'headers_signature'
    ];

    protected $hidden = [
        'deleted_at',
        'updated_at',
        'short_url_id',
        'location_id',
        'outcome_id',
    ];

    public function shortUrl(): HasOne
    {
        return $this->hasOne(ShortUrl::class, 'id', 'short_url_id');
    }

    public function location(): HasOne
    {
        return $this->hasOne(ShortUrlLocation::class, 'id', 'location_id');
    }

    public function outcome(): HasOne
    {
        return $this->hasOne(ShortUrlOutcome::class, 'id', 'outcome_id');
    }

    /**
     * @param  Builder  $query
     */
    public function newEloquentBuilder($query): ClickQueryBuilder
    {
        return new ClickQueryBuilder($query);
    }

    public function scopeSearch($query, $keyword)
    {
        $query->whereHas('location', function ($q) use ($keyword) {
            $q->where('countryName', 'like', '%'.$keyword.'%')
                ->orWhere('countryCode', 'like', '%'.$keyword.'%')
                ->orWhere('regionName', 'like', '%'.$keyword.'%')
                ->orWhere('regionCode', 'like', '%'.$keyword.'%')
                ->orWhere('cityName', 'like', '%'.$keyword.'%')
                ->orWhere('zipCode', 'like', '%'.$keyword.'%')
                ->orWhere('postalCode', 'like', '%'.$keyword.'%')
                ->orWhere('timezone', 'like', '%'.$keyword.'%')
                ->orWhere('metroCode', 'like', '%'.$keyword.'%')
                ->orWhere('isoCode', 'like', '%'.$keyword.'%')
                ->orWhere('ip', 'like', '%'.$keyword.'%');
        });

        // Eager load the 'outcomes' relationship
        $query->orWhereHas('outcome', function ($q) use ($keyword) {
            $q->where('alias', 'like', '%'.$keyword.'%');
        });

        return $query;
    }
}

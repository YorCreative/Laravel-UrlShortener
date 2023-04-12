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
}

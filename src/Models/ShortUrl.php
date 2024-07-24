<?php

namespace YorCreative\UrlShortener\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use YorCreative\UrlShortener\Traits\PublishableHasFactory;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

class ShortUrl extends Model
{
    use PublishableHasFactory, ShortUrlHelper, SoftDeletes;

    /**
     * @var bool
     */
    public $incrementing = true;

    /**
     * @var string
     */
    protected $table = 'short_urls';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string[]
     */
    protected $fillable = [
        'plain_text',
        'hashed',
        'domain',
        'identifier',
        'activation',
        'expiration',
        'password',
        'limit',
        'branded',
    ];

    protected $hidden = [
        'password',
        'deleted_at',
        'laravel_through_key',
    ];

    public function tracing(): HasOne
    {
        return $this->hasOne(ShortUrlTracing::class, 'short_url_id');
    }

    public function hasPassword(): bool
    {
        return ! is_null($this->password);
    }

    public function hasExpiration(): bool
    {
        return ! is_null($this->expiration);
    }

    public function hasActivation(): bool
    {
        return ! is_null($this->activation);
    }

    public function hasLimit(): bool
    {
        return ! is_null($this->limit);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(ShortUrlClick::class, 'short_url_id', 'id');
    }

    public function ownership(): HasOne
    {
        return $this->hasOne(ShortUrlOwnership::class);
    }

    public function plainText(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => $this->removeDuplicateShortUrlQueryTag($value));
    }

    public function scopeSearch($query, $keyword)
    {
        $query->where('plain_text', 'LIKE', "%{$keyword}%")
            ->orWhere('identifier', 'LIKE', "%{$keyword}%")
            ->orWhere('domain', 'LIKE', "%{$keyword}%")
            ->orWhere('activation', 'LIKE', "%{$keyword}%")
            ->orWhere('expiration', 'LIKE', "%{$keyword}%");

        return $query;
    }

    public function scopeExpiringInDays($query, $days)
    {
        $timestamp = Carbon::now()->addDays($days)->timestamp;

        $query->where('expiration', '<=', "$timestamp");

        return $query;
    }

    public function scopeHasTracing($query, $search)
    {
        $query->whereHas('tracing')
            ->whereIn('short_urls.id', function ($subQuery) use ($search) {
                $subQuery->from('short_url_tracings')
                    ->where(function ($subWhereQuery) use ($search) {
                        $subWhereQuery->where('short_url_tracings.utm_source', 'like', '%'.$search.'%')
                            ->orWhere('short_url_tracings.utm_medium', 'like', '%'.$search.'%')
                            ->orWhere('short_url_tracings.utm_campaign', 'like', '%'.$search.'%')
                            ->orWhere('short_url_tracings.utm_content', 'like', '%'.$search.'%')
                            ->orWhere('short_url_tracings.utm_term', 'like', '%'.$search.'%');
                    })
                    ->select('short_url_tracings.short_url_id');
            });

        return $query;
    }
}

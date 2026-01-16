<?php

namespace YorCreative\UrlShortener\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use YorCreative\UrlShortener\Traits\PublishableHasFactory;

class ShortUrl extends Model
{
    use PublishableHasFactory, SoftDeletes;

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
        'domain',
        'plain_text',
        'hashed',
        'identifier',
        'activation',
        'expiration',
        'password',
        'limit',
    ];

    protected $hidden = [
        'password',
        'deleted_at',
        'laravel_through_key',
    ];

    public function tracing(): HasOne
    {
        return $this->hasOne(ShortUrlTracing::class, 'short_url_id', 'id');
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

    /**
     * Relationship to domain configuration (if using database).
     */
    public function domainConfig(): BelongsTo
    {
        return $this->belongsTo(ShortUrlDomain::class, 'domain', 'domain');
    }

    /**
     * Scope for domain filtering.
     */
    public function scopeForDomain(Builder $query, ?string $domain = null): Builder
    {
        if ($domain === null) {
            return $query->whereNull('domain');
        }

        return $query->where('domain', $domain);
    }

    /**
     * Check if URL belongs to specific domain.
     */
    public function isOnDomain(?string $domain): bool
    {
        return $this->domain === $domain;
    }

    /**
     * Check if URL has a domain set.
     */
    public function hasDomain(): bool
    {
        return ! is_null($this->domain);
    }
}

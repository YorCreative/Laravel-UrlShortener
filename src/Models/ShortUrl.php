<?php

namespace YorCreative\UrlShortener\Models;

use Illuminate\Database\Eloquent\Model;
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

    /**
     * @return HasOne
     */
    public function tracing(): HasOne
    {
        return $this->hasOne(ShortUrlTracing::class, 'short_url_id', 'id');
    }

    /**
     * @return bool
     */
    public function hasPassword(): bool
    {
        return ! is_null($this->password);
    }

    /**
     * @return bool
     */
    public function hasExpiration(): bool
    {
        return ! is_null($this->expiration);
    }

    /**
     * @return bool
     */
    public function hasActivation(): bool
    {
        return ! is_null($this->activation);
    }

    /**
     * @return bool
     */
    public function hasLimit(): bool
    {
        return ! is_null($this->limit);
    }

    /**
     * @return HasMany
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(ShortUrlClick::class, 'short_url_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function ownership(): HasOne
    {
        return $this->hasOne(ShortUrlOwnership::class);
    }
}

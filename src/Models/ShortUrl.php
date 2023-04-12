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
}

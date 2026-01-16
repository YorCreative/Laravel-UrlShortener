<?php

namespace YorCreative\UrlShortener\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use YorCreative\UrlShortener\Traits\PublishableHasFactory;

class ShortUrlDomain extends Model
{
    use PublishableHasFactory, SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'short_url_domains';

    /**
     * @var string[]
     */
    protected $fillable = [
        'domain',
        'prefix',
        'is_active',
        'settings',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get all short URLs for this domain.
     */
    public function shortUrls(): HasMany
    {
        return $this->hasMany(ShortUrl::class, 'domain', 'domain');
    }

    /**
     * Get the identifier length for this domain.
     */
    public function getIdentifierLengthAttribute(): int
    {
        $settings = $this->settings ?? [];

        return $settings['identifier_length']
            ?? config('urlshortener.branding.identifier.length', 6);
    }

    /**
     * Get the redirect code for this domain.
     */
    public function getRedirectCodeAttribute(): int
    {
        $settings = $this->settings ?? [];

        return $settings['redirect_code']
            ?? config('urlshortener.redirect.code', 307);
    }
}

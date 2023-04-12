<?php

namespace YorCreative\UrlShortener\Traits;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Models\ShortUrlOwnership;

trait Ownerable
{
    public function getShortUrls(): HasManyThrough
    {
        return $this->hasManyThrough(
            ShortUrl::class,
            ShortUrlOwnership::class,
            'ownerable_id',
            'id',
            'id',
            'short_url_id'
        );
    }
}

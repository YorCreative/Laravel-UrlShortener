<?php

namespace YorCreative\UrlShortener\Events;

use Illuminate\Foundation\Events\Dispatchable;
use YorCreative\UrlShortener\Models\ShortUrl;

class ShortUrlExpired
{
    use Dispatchable;

    public function __construct(
        public readonly ShortUrl $shortUrl,
        public readonly string $identifier,
        public readonly ?string $domain = null,
    ) {}
}

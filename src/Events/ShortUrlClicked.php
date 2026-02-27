<?php

namespace YorCreative\UrlShortener\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ShortUrlClicked
{
    use Dispatchable;

    public function __construct(
        public readonly string $identifier,
        public readonly int $outcomeId,
        public readonly string $requestIp,
        public readonly ?string $domain = null,
    ) {}
}

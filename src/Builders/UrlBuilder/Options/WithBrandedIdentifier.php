<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder\Options;

use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilderOptionInterface;

class WithBrandedIdentifier implements UrlBuilderOptionInterface
{
    public function resolve(Collection &$shortUrlCollection): void
    {
        $shortUrlCollection = $shortUrlCollection->merge([
            'identifier' => $shortUrlCollection->get('identifier'),
        ]);
    }
}

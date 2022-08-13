<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder;

use Illuminate\Support\Collection;

interface UrlBuilderOptionInterface
{
    public function resolve(Collection &$shortUrlCollection): void;
}

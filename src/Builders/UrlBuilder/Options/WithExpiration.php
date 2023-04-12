<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder\Options;

use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilderOptionInterface;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Repositories\UrlRepository;

class WithExpiration implements UrlBuilderOptionInterface
{
    /**
     * @throws UrlRepositoryException
     */
    public function resolve(Collection &$shortUrlCollection): void
    {
        UrlRepository::updateShortUrl(
            $shortUrlCollection->get('identifier'),
            ['expiration' => $shortUrlCollection->get('expiration')]
        );
    }
}

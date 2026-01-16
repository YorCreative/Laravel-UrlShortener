<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder\Options;

use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilderOptionInterface;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Repositories\UrlRepository;

class WithPassword implements UrlBuilderOptionInterface
{
    /**
     * @throws UrlRepositoryException
     */
    public function resolve(Collection &$shortUrlCollection): void
    {
        $identifier = $shortUrlCollection->get('identifier');
        $domain = $shortUrlCollection->get('domain');

        if (config('urlshortener.domains.enabled', false)) {
            UrlRepository::updateShortUrlForDomain(
                $identifier,
                ['password' => $shortUrlCollection->get('password')],
                $domain
            );
        } else {
            UrlRepository::updateShortUrl(
                $identifier,
                ['password' => $shortUrlCollection->get('password')]
            );
        }
    }
}

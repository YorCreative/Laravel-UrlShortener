<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder\Options;

use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilderOptionInterface;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Repositories\UrlRepository;

class WithOpenLimit implements UrlBuilderOptionInterface
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
                ['limit' => $shortUrlCollection->get('limit')],
                $domain
            );
        } else {
            UrlRepository::updateShortUrl(
                $identifier,
                ['limit' => $shortUrlCollection->get('limit')]
            );
        }
    }
}

<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder\Options;

use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilderOptionInterface;
use YorCreative\UrlShortener\Exceptions\UrlBuilderException;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Repositories\UrlRepository;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

class BaseOption implements UrlBuilderOptionInterface
{
    use ShortUrlHelper;

    /**
     * @throws UrlBuilderException
     * @throws UrlRepositoryException
     */
    public function resolve(Collection &$shortUrlCollection): void
    {
        $identifier = $this->generateUrlIdentifier();

        $shortUrlCollection = $shortUrlCollection->merge([
            'identifier' => $identifier,
        ]);

        if (UrlRepository::hashExists($shortUrlCollection->get('hashed'))) {
            throw new UrlBuilderException('A short url already exists for the long url provided.');
        }

        UrlRepository::create($shortUrlCollection->only([
            'plain_text', 'hashed', 'identifier',
        ])->toArray());
    }
}

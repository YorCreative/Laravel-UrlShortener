<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder\Options;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
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
        $domain = $shortUrlCollection->get('domain');
        $identifier = $shortUrlCollection->has('identifier')
            ? $shortUrlCollection->get('identifier')
            : $this->generateUrlIdentifier();

        $shortUrlCollection = $shortUrlCollection->merge([
            'identifier' => $identifier,
            'domain' => $domain,
        ]);

        $shortUrlArr = $shortUrlCollection->only([
            'plain_text', 'hashed', 'identifier',
        ])->toArray();

        if (UrlRepository::hashExists($shortUrlCollection->get('hashed'))) {
            if (Config::get('urlshortener.allow_duplicate_long_links')) {
                $plain_text = $shortUrlArr['plain_text'] .= $this->getDuplicateShortUrlQueryTag();

                $shortUrlCollection->put('hashed', md5($plain_text));
                $shortUrlCollection->put('plain_text', $plain_text);
            } else {
                throw new UrlBuilderException('A short url already exists for the long url provided.');
            }
        }

        UrlRepository::create($shortUrlCollection->only(['domain', 'plain_text', 'hashed', 'identifier'])->toArray());
    }
}

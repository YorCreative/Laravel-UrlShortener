<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder\Options;

use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilderOptionInterface;
use YorCreative\UrlShortener\Exceptions\TracingRepositoryException;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Repositories\TracingRepository;
use YorCreative\UrlShortener\Repositories\UrlRepository;

class WithTracing implements UrlBuilderOptionInterface
{
    /**
     * @throws UrlRepositoryException
     * @throws TracingRepositoryException
     */
    public function resolve(Collection &$shortUrlCollection): void
    {
        $sanitizedUtmParameters = TracingRepository::sanitizeUtmArray(
            $shortUrlCollection->get('utm_parameters')
        );

        $trace = [
            'short_url_id' => UrlRepository::findByIdentifier(
                $shortUrlCollection->get('identifier')
            )->id,
        ];

        $trace = array_merge($trace, $sanitizedUtmParameters);

        TracingRepository::create($trace);
    }
}

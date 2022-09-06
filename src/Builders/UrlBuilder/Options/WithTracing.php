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
     * @param  Collection  $shortUrlCollection
     *
     * @throws UrlRepositoryException
     * @throws TracingRepositoryException
     */
    public function resolve(Collection &$shortUrlCollection): void
    {
        $utm_parameters = $shortUrlCollection->get('utm_parameters');

        $allowed_utm_parameters = array_intersect(
            array_keys($utm_parameters),
            TracingRepository::getAllowedParameters()
        );

        $trace = [
            'short_url_id' => UrlRepository::findByIdentifier(
                $shortUrlCollection->get('identifier')
            )->id,
        ];

        foreach ($allowed_utm_parameters as $parameter) {
            $trace = array_merge($trace, [$parameter => $utm_parameters[$parameter]]);
        }

        TracingRepository::create($trace);
    }
}

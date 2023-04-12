<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder\Options;

use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilderOptionInterface;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Services\UrlService;

class WithOwnership implements UrlBuilderOptionInterface
{
    /**
     * @throws UrlRepositoryException
     */
    public function resolve(Collection &$shortUrlCollection): void
    {
        $model = $shortUrlCollection->get('owner_model');
        $primary_key = $model->getKeyName();

        UrlService::attachOwnership(
            $shortUrlCollection->get('identifier'),
            $model->getMorphClass(),
            $model->$primary_key
        );
    }
}

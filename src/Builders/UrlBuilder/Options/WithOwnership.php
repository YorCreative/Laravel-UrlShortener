<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder\Options;

use Illuminate\Database\Eloquent\Model;
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

        if ($model === null || ! $model instanceof Model) {
            throw new UrlRepositoryException('Owner model is not set or is not a valid Model instance.');
        }

        $primary_key = $model->getKeyName();
        $domain = $shortUrlCollection->get('domain');

        UrlService::attachOwnership(
            $shortUrlCollection->get('identifier'),
            $model->getMorphClass(),
            $model->$primary_key,
            $domain
        );
    }
}

<?php

namespace YorCreative\UrlShortener\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Exceptions\UtilityServiceException;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Repositories\UrlRepository;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

class UrlService
{
    use ShortUrlHelper;

    /**
     * @param  string  $hash
     * @return ShortUrl|null
     *
     * @throws UrlRepositoryException
     */
    public static function findByHash(string $hash): ?ShortUrl
    {
        return UrlRepository::findByHash($hash);
    }

    /**
     * @param  string  $plain_text
     * @return ShortUrl|null
     *
     * @throws UrlRepositoryException
     */
    public static function findByPlainText(string $plain_text): ?ShortUrl
    {
        return UrlRepository::findByPlainText($plain_text);
    }

    /**
     * @param  string  $identifier
     * @return ShortUrl|null
     *
     * @throws UrlRepositoryException
     */
    public static function findByIdentifier(string $identifier): ?ShortUrl
    {
        return UrlRepository::findByIdentifier($identifier);
    }

    /**
     * @param  array  $utm_combination
     * @return Collection
     *
     * @throws UrlRepositoryException
     */
    public static function findByUtmCombination(array $utm_combination): Collection
    {
        return UrlRepository::findByUtmCombination($utm_combination);
    }

    /**
     * @param  string  $identifier
     * @param  string  $password
     * @return ShortUrl|null
     *
     * @throws UrlRepositoryException
     * @throws UtilityServiceException
     */
    public static function attempt(string $identifier, string $password): ?ShortUrl
    {
        if (! $shortUrl = UrlRepository::findByIdentifier($identifier)) {
            return null;
        }

        return UtilityService::getEncrypter()->decryptString($shortUrl->password) == $password
            ? $shortUrl
            : null;
    }

    /**
     * @param $identifier
     * @param $type
     * @param $id
     *
     * @throws UrlRepositoryException
     */
    public static function attachOwnership($identifier, $type, $id): void
    {
        try {
            UrlRepository::findOrCreateOwnershipRecord([
                'short_url_id' => UrlRepository::findByIdentifier($identifier)->id,
                'ownerable_type' => $type,
                'ownerable_id' => $id,
            ]);
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    /**
     * @param  string  $plain_text
     * @return UrlBuilder
     */
    public static function shorten(string $plain_text): UrlBuilder
    {
        return UrlBuilder::shorten($plain_text);
    }
}

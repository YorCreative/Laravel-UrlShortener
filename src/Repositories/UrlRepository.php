<?php

namespace YorCreative\UrlShortener\Repositories;

use Exception;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Models\ShortUrlOwnership;

class UrlRepository
{
    /**
     * @param  array  $ownership
     * @return mixed
     *
     * @throws UrlRepositoryException
     */
    public static function findOrCreateOwnershipRecord(array $ownership): ShortUrlOwnership
    {
        try {
            try {
                return ShortUrlOwnership::where('short_url_id', $ownership['short_url_id'])
                    ->where('ownerable_type', $ownership['ownerable_type'])
                    ->where('ownerable_id', $ownership['ownerable_id'])
                    ->firstOrFail();
            } catch (Exception $exception) {
                return ShortUrlOwnership::create($ownership);
            }
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    /**
     * @param  string  $hash
     * @return mixed
     *
     * @throws UrlRepositoryException
     */
    public static function findByHash(string $hash): ShortUrl
    {
        try {
            return ShortUrl::where(
                'hashed', $hash
            )->firstOrFail();
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    /**
     * @param  string  $plain_text
     * @return mixed
     *
     * @throws UrlRepositoryException
     */
    public static function findByPlainText(string $plain_text): ShortUrl
    {
        try {
            return ShortUrl::where(
                'plain_text', $plain_text
            )->firstOrFail();
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function create(array $ShortUrl): ShortUrl
    {
        try {
            return ShortUrl::create($ShortUrl);
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    /**
     * @param  string  $identifier
     * @param  array  $updates
     * @return ShortUrl
     *
     * @throws UrlRepositoryException
     */
    public static function updateShortUrl(string $identifier, array $updates): ShortUrl
    {
        try {
            $ShortUrlRecord = self::findByIdentifier($identifier);
            $ShortUrlRecord->update($updates);

            return $ShortUrlRecord;
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    /**
     * @param  string  $identifier
     * @return bool
     */
    public static function identifierExists(string $identifier): bool
    {
        return (new ShortUrl())->where('identifier', $identifier)->exists();
    }

    /**
     * @param  string  $hashed
     * @return ShortUrl|null
     */
    public static function hashExists(string $hashed): ?ShortUrl
    {
        return (new ShortUrl())->where('hashed', $hashed)->first();
    }

    /**
     * @param  string  $shortUrl
     * @return ShortUrl
     *
     * @throws UrlRepositoryException
     */
    public static function findByIdentifier(string $identifier): ShortUrl
    {
        try {
            return ShortUrl::where(
                'identifier', $identifier
            )->firstOrFail();
        } catch (Exception $exception) {
            throw new UrlRepositoryException('Unable to find short url identifier: '.$identifier);
        }
    }
}

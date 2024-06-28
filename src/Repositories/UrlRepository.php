<?php

namespace YorCreative\UrlShortener\Repositories;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Models\ShortUrlOwnership;

class UrlRepository
{
    /**
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
     * @throws UrlRepositoryException
     */
    public static function findOwnershipUrls(string $owner_type, int $owner_id): Collection
    {
        try {
            return ShortUrl::whereIn('id', function ($query) use ($owner_type, $owner_id) {
                $query->from('short_url_ownerships');
                $query->where([
                    'ownerable_type' => $owner_type,
                    'ownerable_id' => $owner_id,
                ]);
                $query->select('short_url_id');
            })->get();
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    /**
     * @return mixed
     *
     * @throws UrlRepositoryException
     */
    public static function findByHash(string $hash): ShortUrl
    {
        try {
            return ShortUrl::where(
                'hashed', $hash
            )->with(self::defaultWithRelationship())->firstOrFail();
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    /**
     * @return mixed
     *
     * @throws UrlRepositoryException
     */
    public static function findByPlainText(string $plain_text): ShortUrl
    {
        try {
            return ShortUrl::where(
                'plain_text', 'like', $plain_text.'%'
            )->with(self::defaultWithRelationship())->firstOrFail();
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
     * @throws UrlRepositoryException
     */
    public static function updateShortUrl(string $identifier, string $domain, array $updates): ShortUrl
    {
        try {
            $shortUrlRecord = self::findByDomainIdentifier($domain, $identifier);
            $shortUrlRecord->update($updates);

            return $shortUrlRecord;
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    public static function identifierExists(string $identifier): bool
    {
        return (new ShortUrl())->where('identifier', $identifier)->exists();
    }

    public static function hashExists(string $hashed): ?ShortUrl
    {
        return (new ShortUrl())->where('hashed', $hashed)->first();
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function findByIdentifier(string $identifier): ?ShortUrl
    {
        return ShortUrl::where(
            'identifier', $identifier
        )->with(self::defaultWithRelationship())->first();
    }

    public static function findByDomainIdentifier(?string $domain, string $identifier): ?ShortUrl
    {
        return ShortUrl::where('identifier', $identifier)
            ->where('domain', $domain)
            ->with(self::defaultWithRelationship())
            ->first();
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function findByUtmCombination(array $utm_combination): Collection
    {
        try {
            // filter out any utm parameters not allowed into the query.
            $sanitized_utm_combination = TracingRepository::sanitizeUtmArray($utm_combination);

            return ShortUrl::whereIn('id', function ($query) use ($sanitized_utm_combination) {
                $query->from('short_url_tracings');
                $query->where($sanitized_utm_combination);
                $query->select('short_url_id');
            })->with(self::defaultWithRelationship())->get();
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    /**
     * @return string[]
     */
    public static function defaultWithRelationship(): array
    {
        return [
            'ownership', 'clicks.location', 'clicks.outcome', 'tracing',
        ];
    }
}

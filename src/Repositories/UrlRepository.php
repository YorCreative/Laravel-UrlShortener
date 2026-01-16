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
     * @throws UrlRepositoryException
     */
    public static function findOrCreateOwnershipRecord(array $ownership): ShortUrlOwnership
    {
        try {
            return ShortUrlOwnership::firstOrCreate([
                'short_url_id' => $ownership['short_url_id'],
                'ownerable_type' => $ownership['ownerable_type'],
                'ownerable_id' => $ownership['ownerable_id'],
            ], $ownership);
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return mixed
     *
     * @throws UrlRepositoryException
     */
    public static function findByHash(string $hash, ?string $domain = null): ShortUrl
    {
        try {
            $query = ShortUrl::where('hashed', $hash);

            if (config('urlshortener.domains.enabled', false)) {
                // Handle NULL domain explicitly since SQL NULL != NULL
                if ($domain === null) {
                    $query->whereNull('domain');
                } else {
                    $query->where('domain', $domain);
                }
            }

            return $query->with(self::defaultWithRelationship())->firstOrFail();
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return mixed
     *
     * @throws UrlRepositoryException
     */
    public static function findByPlainText(string $plain_text, ?string $domain = null): ShortUrl
    {
        try {
            $query = ShortUrl::where('plain_text', $plain_text);

            if (config('urlshortener.domains.enabled', false)) {
                // Handle NULL domain explicitly since SQL NULL != NULL
                if ($domain === null) {
                    $query->whereNull('domain');
                } else {
                    $query->where('domain', $domain);
                }
            }

            return $query->with(self::defaultWithRelationship())->firstOrFail();
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Find a ShortUrl by plain text, returning null if not found.
     */
    public static function findByPlainTextOrNull(string $plain_text, ?string $domain = null): ?ShortUrl
    {
        $query = ShortUrl::where('plain_text', $plain_text);

        if (config('urlshortener.domains.enabled', false)) {
            // Handle NULL domain explicitly since SQL NULL != NULL
            if ($domain === null) {
                $query->whereNull('domain');
            } else {
                $query->where('domain', $domain);
            }
        }

        return $query->with(self::defaultWithRelationship())->first();
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function create(array $ShortUrl): ShortUrl
    {
        try {
            return ShortUrl::create($ShortUrl);
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function updateShortUrl(string $identifier, array $updates): ShortUrl
    {
        try {
            $ShortUrlRecord = self::findByIdentifier($identifier);
            $ShortUrlRecord->update($updates);

            return $ShortUrlRecord;
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Check if an identifier exists, with proper NULL domain handling.
     * SQL NULL != NULL, so we need explicit NULL checks for uniqueness.
     */
    public static function identifierExists(string $identifier, ?string $domain = null): bool
    {
        $query = (new ShortUrl())->where('identifier', $identifier);

        if (config('urlshortener.domains.enabled', false)) {
            // Handle NULL domain explicitly since SQL NULL != NULL
            if ($domain === null) {
                $query->whereNull('domain');
            } else {
                $query->where('domain', $domain);
            }
        }

        return $query->exists();
    }

    /**
     * Check if a hash exists, with proper NULL domain handling.
     */
    public static function hashExists(string $hashed, ?string $domain = null): ?ShortUrl
    {
        $query = (new ShortUrl())->where('hashed', $hashed);

        if (config('urlshortener.domains.enabled', false)) {
            // Handle NULL domain explicitly since SQL NULL != NULL
            if ($domain === null) {
                $query->whereNull('domain');
            } else {
                $query->where('domain', $domain);
            }
        }

        return $query->first();
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function findByIdentifier(string $identifier, ?string $domain = null): ShortUrl
    {
        try {
            $query = ShortUrl::where('identifier', $identifier);

            if (config('urlshortener.domains.enabled', false)) {
                // Handle NULL domain explicitly since SQL NULL != NULL
                if ($domain === null) {
                    $query->whereNull('domain');
                } else {
                    $query->where('domain', $domain);
                }
            }

            return $query->with(self::defaultWithRelationship())->firstOrFail();
        } catch (Exception $exception) {
            throw new UrlRepositoryException('Unable to find short url identifier: '.$identifier, 0, $exception);
        }
    }

    /**
     * Find all short URLs for a specific domain.
     */
    public static function findByDomain(string $domain): Collection
    {
        return ShortUrl::where('domain', $domain)
            ->with(self::defaultWithRelationship())
            ->get();
    }

    /**
     * Update short URL by identifier with optional domain scoping.
     *
     * @throws UrlRepositoryException
     */
    public static function updateShortUrlForDomain(string $identifier, array $updates, ?string $domain = null): ShortUrl
    {
        try {
            $ShortUrlRecord = self::findByIdentifier($identifier, $domain);
            $ShortUrlRecord->update($updates);

            return $ShortUrlRecord;
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function findByUtmCombination(array $utm_combination, ?string $domain = null): Collection
    {
        try {
            // filter out any utm parameters not allowed into the query.
            $sanitized_utm_combination = TracingRepository::sanitizeUtmArray($utm_combination);

            $query = ShortUrl::whereIn('id', function ($query) use ($sanitized_utm_combination) {
                $query->from('short_url_tracings');
                $query->where($sanitized_utm_combination);
                $query->select('short_url_id');
            });

            // Add domain filtering if multi-domain enabled
            if (config('urlshortener.domains.enabled', false)) {
                // Handle NULL domain explicitly since SQL NULL != NULL
                if ($domain === null) {
                    $query->whereNull('domain');
                } else {
                    $query->where('domain', $domain);
                }
            }

            return $query->with(self::defaultWithRelationship())->get();
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage(), 0, $exception);
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

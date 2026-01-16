<?php

namespace YorCreative\UrlShortener\Repositories;

use Exception;
use YorCreative\UrlShortener\Exceptions\ClickRepositoryException;
use YorCreative\UrlShortener\Models\ShortUrlClick;

class ClickRepository
{
    /**
     * @throws ClickRepositoryException
     */
    public static function findById(int $id, array $with = []): ShortUrlClick
    {
        try {
            return ShortUrlClick::where('id', $id)
                ->with(empty($with) ? self::defaultWithRelations() : $with)
                ->firstOrFail();
        } catch (Exception $exception) {
            throw new ClickRepositoryException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Find a click by ID with domain validation.
     * Ensures the click belongs to a short URL on the specified domain.
     *
     * @throws ClickRepositoryException
     */
    public static function findByIdForDomain(int $id, ?string $domain = null, array $with = []): ShortUrlClick
    {
        try {
            $query = ShortUrlClick::where('id', $id)
                ->with(empty($with) ? self::defaultWithRelations() : $with);

            // If multi-domain is enabled, validate the click belongs to a URL on this domain
            if (config('urlshortener.domains.enabled', false)) {
                $query->whereHas('shortUrl', function ($q) use ($domain) {
                    if ($domain === null) {
                        $q->whereNull('domain');
                    } else {
                        $q->where('domain', $domain);
                    }
                });
            }

            return $query->firstOrFail();
        } catch (Exception $exception) {
            throw new ClickRepositoryException('Click not found or access denied for domain', 0, $exception);
        }
    }

    /**
     * @throws ClickRepositoryException
     */
    public static function createClick(int $short_url_id, int $location_id, int $outcome_id): void
    {
        try {
            ShortUrlClick::create([
                'short_url_id' => $short_url_id,
                'location_id' => $location_id,
                'outcome_id' => $outcome_id,
            ]);
        } catch (Exception $exception) {
            throw new ClickRepositoryException($exception->getMessage(), 0, $exception);
        }
    }


    /**
     * @return string[]
     */
    public static function defaultWithRelations(): array
    {
        return ['location', 'outcome', 'shortUrl.tracing'];
    }
}

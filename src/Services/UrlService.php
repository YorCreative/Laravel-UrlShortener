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
     * @throws UrlRepositoryException
     */
    public static function findByHash(string $hash): ?ShortUrl
    {
        return UrlRepository::findByHash($hash);
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function findByPlainText(string $plain_text): ?ShortUrl
    {
        return UrlRepository::findByPlainText($plain_text);
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function findByIdentifier(string $identifier, ?string $domain = null): ?ShortUrl
    {
        return $domain
            ? UrlRepository::findByDomainIdentifier($domain, $identifier)
            : UrlRepository::findByIdentifier($identifier);
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function findByUtmCombination(array $utm_combination): Collection
    {
        return UrlRepository::findByUtmCombination($utm_combination);
    }

    /**
     * @throws UrlRepositoryException
     * @throws UtilityServiceException
     */
    public static function attempt(string $identifier, ?string $domain, string $password): ?ShortUrl
    {
        if (! $shortUrl = UrlRepository::findByDomainIdentifier($domain, $identifier)) {
            return null;
        }

        // TODO: move this over to main
        if ($shortUrl->hasPassword()) {
            return UtilityService::getEncrypter()->decryptString($shortUrl->password) == $password
                ? $shortUrl
                : null;
        }

        return null;
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function attachOwnership($domain, $identifier, $type, $id): void
    {
        try {
            UrlRepository::findOrCreateOwnershipRecord([
                'short_url_id' => UrlRepository::findByDomainIdentifier($domain, $identifier)->id,
                'ownerable_type' => $type,
                'ownerable_id' => $id,
            ]);
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    public static function shorten(string $plain_text, ?string $domain = null): UrlBuilder
    {
        return UrlBuilder::shorten($plain_text, $domain);
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function getAllByOwner(string $ownerable_type, string $ownerable_id): Collection
    {
        return UrlRepository::findOwnershipUrls($ownerable_type, $ownerable_id);
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function getAllByOwnerCount(string $ownerable_type, string $ownerable_id): int
    {
        return UrlRepository::getOwnershipUrlsCount($ownerable_type,$ownerable_id);
    }
}

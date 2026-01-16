<?php

namespace YorCreative\UrlShortener\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\RateLimiter;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Exceptions\RateLimitExceededException;
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
    public static function findByHash(string $hash, ?string $domain = null): ?ShortUrl
    {
        return UrlRepository::findByHash($hash, $domain);
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function findByPlainText(string $plain_text, ?string $domain = null): ?ShortUrl
    {
        return UrlRepository::findByPlainText($plain_text, $domain);
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function findByIdentifier(string $identifier, ?string $domain = null): ?ShortUrl
    {
        return UrlRepository::findByIdentifier($identifier, $domain);
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function findByUtmCombination(array $utm_combination, ?string $domain = null): Collection
    {
        return UrlRepository::findByUtmCombination($utm_combination, $domain);
    }

    /**
     * Attempt to access a password-protected short URL.
     *
     * @throws UrlRepositoryException
     * @throws UtilityServiceException
     * @throws RateLimitExceededException
     */
    public static function attempt(string $identifier, string $password, ?string $domain = null, ?string $ipAddress = null): ?ShortUrl
    {
        // Rate limiting key based on IP + identifier
        $rateLimitKey = self::getRateLimitKey($identifier, $ipAddress);

        // Check if rate limited
        if (self::isRateLimited($rateLimitKey)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);
            throw new RateLimitExceededException(
                'Too many password attempts. Please try again later.',
                $retryAfter
            );
        }

        if (! $shortUrl = UrlRepository::findByIdentifier($identifier, $domain)) {
            self::incrementRateLimitAttempt($rateLimitKey);

            return null;
        }

        // URL doesn't have password protection - still increment rate limit to prevent enumeration
        if (! $shortUrl->hasPassword() || empty($shortUrl->password)) {
            self::incrementRateLimitAttempt($rateLimitKey);

            return null;
        }

        // Attempt to decrypt password - handle decryption failures gracefully
        try {
            $decryptedPassword = UtilityService::getEncrypter()->decryptString($shortUrl->password);
        } catch (Exception $e) {
            // Decryption failed (corrupted data or wrong key) - treat as wrong password
            self::incrementRateLimitAttempt($rateLimitKey);

            return null;
        }

        if (! hash_equals($decryptedPassword, $password)) {
            self::incrementRateLimitAttempt($rateLimitKey);

            return null;
        }

        // Successful attempt - clear rate limit for this key
        RateLimiter::clear($rateLimitKey);

        return $shortUrl;
    }

    /**
     * Generate rate limit key for password attempts.
     * Uses IP address + identifier for per-user, per-URL rate limiting.
     */
    protected static function getRateLimitKey(string $identifier, ?string $ipAddress = null): string
    {
        // Get IP from parameter, request, or generate a unique fallback
        $ip = $ipAddress ?? request()->ip();

        // If IP is still null, use a hash of available request data as fallback
        // This prevents all unknown IPs from sharing the same rate limit bucket
        if (empty($ip)) {
            $ip = 'noid_'.substr(md5(
                ($ipAddress ?? '').
                request()->userAgent().
                request()->header('Accept-Language', '').
                $identifier
            ), 0, 16);
        }

        return 'urlshortener:password_attempt:'.$ip.':'.$identifier;
    }

    /**
     * Check if the given key is currently rate limited.
     */
    protected static function isRateLimited(string $key): bool
    {
        $maxAttempts = config('urlshortener.protection.rate_limit.max_attempts', 5);

        return RateLimiter::tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Increment the rate limit attempt counter.
     */
    protected static function incrementRateLimitAttempt(string $key): void
    {
        $decayMinutes = config('urlshortener.protection.rate_limit.decay_minutes', 1);

        RateLimiter::hit($key, $decayMinutes * 60);
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function attachOwnership($identifier, $type, $id, ?string $domain = null): void
    {
        try {
            UrlRepository::findOrCreateOwnershipRecord([
                'short_url_id' => UrlRepository::findByIdentifier($identifier, $domain)->id,
                'ownerable_type' => $type,
                'ownerable_id' => $id,
            ]);
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage(), 0, $exception);
        }
    }

    public static function shorten(string $plain_text): UrlBuilder
    {
        return UrlBuilder::shorten($plain_text);
    }

    /**
     * Find an existing short URL by plain text or create a new one.
     * Returns the existing ShortUrl model if found, otherwise returns a UrlBuilder for building a new one.
     */
    public static function findOrCreate(string $plain_text, ?string $domain = null): ShortUrl|UrlBuilder
    {
        $existing = UrlRepository::findByPlainTextOrNull($plain_text, $domain);

        if ($existing) {
            return $existing;
        }

        $builder = self::shorten($plain_text);

        // If domain is specified, apply it to the builder
        if ($domain !== null && config('urlshortener.domains.enabled', false)) {
            $builder->forDomain($domain);
        }

        return $builder;
    }

    /**
     * Find all short URLs for a specific domain.
     */
    public static function findByDomain(string $domain): \Illuminate\Database\Eloquent\Collection
    {
        return UrlRepository::findByDomain($domain);
    }
}

<?php

namespace YorCreative\UrlShortener\Services;

use Exception;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Exceptions\UrlServiceException;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Repositories\UrlRepository;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

class UrlService
{
    use ShortUrlHelper;

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
     * @param  string  $identifier
     * @param  string  $password
     * @return ShortUrl|null
     *
     * @throws UrlRepositoryException
     * @throws UrlServiceException
     */
    public static function attempt(string $identifier, string $password): ?ShortUrl
    {
        if (! $shortUrl = UrlRepository::findByIdentifier($identifier)) {
            return null;
        }

        return UrlService::getEncrypter()->decryptString($shortUrl->password) == $password
            ? $shortUrl
            : null;
    }

    /**
     * @return Encrypter
     *
     * @throws UrlServiceException
     */
    public static function getEncrypter(): Encrypter
    {
        try {
            return new Encrypter(
                UrlService::databaseEncryptionKey(),
                'AES-256-CBC');
        } catch (Exception $exception) {
            throw new UrlServiceException($exception->getMessage());
        }
    }

    /**
     * @return string|null
     */
    protected static function databaseEncryptionKey(): ?string
    {
        $key = config('urlshortener.protection.cipher_key');
        $key = is_null($key)
            ? 'base64:44mfXzhGl4IiILZ8sRfzkOZ4b26m9ygXmTRYjOE9Ylk='
            : $key;

        return base64_decode(Str::after($key, 'base64:'));
    }

    /**
     * @return int
     */
    public static function getRedirectCode(): int
    {
        return config('urlshortener.redirect.code') ?? 307;
    }

    /**
     * @param  Request  $request
     * @return array
     */
    public static function getRedirectHeaders(Request $request): array
    {
        return UrlRepository::constructRedirectHeaders([
            'X-Forwarded-For' => $request->ip(),
        ]);
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

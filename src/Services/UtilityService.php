<?php

namespace YorCreative\UrlShortener\Services;

use Exception;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use YorCreative\UrlShortener\Exceptions\UtilityServiceException;

class UtilityService
{
    /**
     * @throws UtilityServiceException
     */
    public static function getEncrypter(): Encrypter
    {
        try {
            return new Encrypter(
                UtilityService::databaseEncryptionKey(),
                'AES-256-CBC');
        } catch (Exception $exception) {
            throw new UtilityServiceException($exception->getMessage());
        }
    }

    /**
     * @throws UtilityServiceException
     */
    protected static function databaseEncryptionKey(): string
    {
        $appKey = env('APP_KEY');

        if (empty($appKey)) {
            throw new UtilityServiceException('APP_KEY environment variable is not set.');
        }

        if (! Str::startsWith($appKey, 'base64:')) {
            throw new UtilityServiceException('APP_KEY must be base64 encoded (should start with "base64:").');
        }

        $decodedKey = base64_decode(Str::after($appKey, 'base64:'));

        if ($decodedKey === false || strlen($decodedKey) !== 32) {
            throw new UtilityServiceException('APP_KEY is invalid. Expected a 32-byte key.');
        }

        return $decodedKey;
    }

    public static function getRedirectCode(?string $domain = null): int
    {
        // Check for domain-specific redirect code if multi-domain is enabled
        if ($domain !== null && config('urlshortener.domains.enabled', false)) {
            $resolver = app(DomainResolver::class);
            $config = $resolver->getConfig($domain);

            if (isset($config['redirect_code'])) {
                return $config['redirect_code'];
            }
        }

        return config('urlshortener.redirect.code') ?? 307;
    }

    public static function getRedirectHeaders(Request $request, ?string $domain = null): array
    {
        return UtilityService::constructRedirectHeaders([
            'X-Forwarded-For' => $request->ip(),
        ]);
    }

    public static function constructRedirectHeaders(array $dynamic_headers = []): array
    {
        return array_merge(
            config('urlshortener.redirect.headers') ?? [
                'Referer' => 'localhost:1337',
            ],
            $dynamic_headers
        );
    }
}

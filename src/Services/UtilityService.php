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
     * @return Encrypter
     *
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
     * @return string|null
     */
    protected static function databaseEncryptionKey(): ?string
    {
        return base64_decode(Str::after(env('APP_KEY'), 'base64:'));
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
        return UtilityService::constructRedirectHeaders([
            'X-Forwarded-For' => $request->ip(),
        ]);
    }

    /**
     * @param  array  $dynamic_headers
     * @return array
     */
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

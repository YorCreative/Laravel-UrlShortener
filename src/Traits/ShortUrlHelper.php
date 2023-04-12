<?php

namespace YorCreative\UrlShortener\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use YorCreative\UrlShortener\Exceptions\UrlServiceException;
use YorCreative\UrlShortener\Repositories\UrlRepository;

trait ShortUrlHelper
{
    /**
     * @throws Throwable
     */
    public static function filterClickValidation(array $filter): array
    {
        $filterValidation = Validator::make($filter, [
            'ownership' => ['array'],
            'ownership.*' => [function ($attribute, $value, $fail) {
                if (! $value instanceof Model) {
                    return $fail('Ownership must be an instance of the owners model.');
                }

                return true;
            }],
            'outcome' => [
                'array',
            ],
            'outcome.*' => [
                'in:1,2,3,4,5',
            ],
            'status' => [
                'in:active,expired,expiring',
            ],
            'identifiers' => [
                'array',
            ],
            'identifiers.*' => [
                'string',
            ],
            'utm_id' => [
                'array',
            ],
            'utm_id.*' => [
                'string',
            ],
            'utm_campaign' => [
                'array',
            ],
            'utm_campaign.*' => [
                'string',
            ],
            'utm_source' => [
                'array',
            ],
            'utm_source.*' => [
                'string',
            ],
            'utm_medium' => [
                'array',
            ],
            'utm_medium.*' => [
                'string',
            ],
            'utm_context' => [
                'array',
            ],
            'utm_context.*' => [
                'string',
            ],
            'utm_term' => [
                'array',
            ],
            'utm_term.*' => [
                'string',
            ],
        ], [
            'outcome.*.in' => 'Invalid outcome id provided.',
        ]);

        throw_if(
            $filterValidation->errors()->isNotEmpty(),
            new UrlServiceException($filterValidation->errors()->toJson())
        );

        return $filter;
    }

    private function generateUrlIdentifier(): string
    {
        $identifier = Str::random(
            config('urlshortener.branding.identifier.length') ?? 6
        );

        if (UrlRepository::identifierExists($identifier)) {
            return $this->generateUrlIdentifier();
        }

        return $identifier;
    }

    private function builtShortUrl($identifier): string
    {
        return str_replace(
            '{identifier}',
            $identifier,
            $this->buildShortUrl()
        );
    }

    private function buildShortUrl(): string
    {
        $host = config('urlshortener.branding.host') ?? 'localhost.test';
        $host = str_ends_with('/', $host)
            ? $host
            : $host.'/';

        $prefix = is_null(config('urlshortener.branding.host')) ? 'v1' : config('urlshortener.branding.prefix');
        $prefix = str_starts_with($prefix, '/')
            ? str_replace($prefix, '/', '')
            : $prefix;

        $prefix = str_ends_with('/', $prefix)
            ? $prefix
            : $prefix.'/';

        $identifier = '{identifier}';

        return is_null($prefix)
            ? $host.$identifier
            : $host.$prefix.$identifier;
    }
}

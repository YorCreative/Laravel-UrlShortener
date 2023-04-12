<?php

namespace YorCreative\UrlShortener\Repositories;

use Exception;
use Illuminate\Http\Request;
use YorCreative\UrlShortener\Exceptions\TracingRepositoryException;
use YorCreative\UrlShortener\Models\ShortUrlTracing;

class TracingRepository
{
    public static string $ID = 'utm_id';

    public static string $SOURCE = 'utm_source';

    public static string $MEDIUM = 'utm_medium';

    public static string $CAMPAIGN = 'utm_campaign';

    public static string $CONTENT = 'utm_content';

    public static string $TERM = 'utm_term';

    /**
     * @throws TracingRepositoryException
     */
    public static function create(array $trace): void
    {
        try {
            ShortUrlTracing::create($trace);
        } catch (Exception $exception) {
            throw new TracingRepositoryException($exception->getMessage());
        }
    }

    public static function hasTracing(Request $request): bool
    {
        return ! empty($request->only(self::getAllowedParameters()));
    }

    protected static function allowedParameters(): array
    {
        return [
            self::$ID,
            self::$SOURCE,
            self::$MEDIUM,
            self::$CAMPAIGN,
            self::$CONTENT,
            self::$TERM,
        ];
    }

    public static function getAllowedParameters(): array
    {
        return self::allowedParameters();
    }

    public static function sanitizeUtmArray(array $utm_input_array): array
    {
        $allowed_utm_parameters = array_intersect(
            array_keys($utm_input_array),
            TracingRepository::getAllowedParameters()
        );

        $sanitized_utm_combination = [];

        foreach ($allowed_utm_parameters as $parameter) {
            $sanitized_utm_combination = array_merge(
                $sanitized_utm_combination,
                [$parameter => $utm_input_array[$parameter]]
            );
        }

        return $sanitized_utm_combination;
    }
}

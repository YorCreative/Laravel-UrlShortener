<?php

namespace YorCreative\UrlShortener\Repositories;

use Exception;
use Illuminate\Http\Request;
use YorCreative\UrlShortener\Exceptions\TracingRepositoryException;
use YorCreative\UrlShortener\Models\ShortUrlTracing;

class TracingRepository
{
    /**
     * @var string
     */
    public static string $ID = 'utm_id';

    /**
     * @var string
     */
    public static string $SOURCE = 'utm_source';

    /**
     * @var string
     */
    public static string $MEDIUM = 'utm_medium';

    /**
     * @var string
     */
    public static string $CAMPAIGN = 'utm_campaign';

    /**
     * @var string
     */
    public static string $CONTENT = 'utm_content';

    /**
     * @var string
     */
    public static string $TERM = 'utm_term';

    /**
     * @param  array  $trace
     * @return void
     *
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

    /**
     * @param  Request  $request
     * @return bool
     */
    public static function hasTracing(Request $request): bool
    {
        return ! empty($request->only(self::getAllowedParameters()));
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    public static function getAllowedParameters(): array
    {
        return self::allowedParameters();
    }

    /**
     * @param  array  $utm_input_array
     * @return array
     */
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

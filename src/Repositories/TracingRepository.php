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
     * @param  Request  $request
     * @return int|null
     *
     * @throws TracingRepositoryException
     */
    public static function create(Request $request): ?int
    {
        if (! self::hasTracing($request)) {
            return null;
        }

        try {
            return ShortUrlTracing::create($request->only(self::getAllowedParameters()))->id;
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
}

<?php

namespace YorCreative\UrlShortener\Repositories;

use Exception;
use Stevebauman\Location\Facades\Location;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Models\ShortUrlLocation;

class LocationRepository
{
    public static function findIp(string $ip)
    {
        return ShortUrlLocation::where('ip', $ip)->first();
    }

    /**
     * @throws UrlRepositoryException
     */
    public static function findOrCreateLocationRecord(array $clickLocation): ShortUrlLocation
    {
        try {
            // Build the unique key attributes - only include fields that are present
            $uniqueAttributes = ['ip' => $clickLocation['ip']];

            if (isset($clickLocation['countryCode'])) {
                $uniqueAttributes['countryCode'] = $clickLocation['countryCode'];
            }

            if (isset($clickLocation['regionCode'])) {
                $uniqueAttributes['regionCode'] = $clickLocation['regionCode'];
            }

            return ShortUrlLocation::firstOrCreate($uniqueAttributes, $clickLocation);
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage(), 0, $exception);
        }
    }

    public static function getLocationFrom(string $ip): array
    {
        $clickLocation = Location::get($ip);

        if (! $clickLocation) {
            return LocationRepository::locationUnknown($ip);
        }

        $clickLocation->longitude = (float) $clickLocation->longitude;
        $clickLocation->latitude = (float) $clickLocation->latitude;

        unset($clickLocation->driver);

        return $clickLocation->toArray();
    }

    public static function locationUnknown(string $ip): array
    {
        return [
            'ip' => $ip,
        ];
    }
}

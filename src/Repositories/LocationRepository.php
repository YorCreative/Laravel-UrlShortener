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
     * @return mixed
     *
     * @throws UrlRepositoryException
     */
    public static function findOrCreateLocationRecord(array $clickLocation): ShortUrlLocation
    {
        try {
            try {
                return ShortUrlLocation::where('ip', $clickLocation['ip'])
                    ->where('countryCode', $clickLocation['countryCode'])
                    ->where('regionCode', $clickLocation['regionCode'])
                    ->firstOrFail();
            } catch (Exception $exception) {
                return ShortUrlLocation::create($clickLocation);
            }
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    public static function getLocationFrom(string $ip): array
    {
        $clickLocation = Location::get($ip);

        if (! $clickLocation) {
            return LocationRepository::locationUnknown($ip);
        }

        $clickLocation['longitude'] = (float) $clickLocation['longitude'];
        $clickLocation['latitude'] = (float) $clickLocation['latitude'];

        unset($clickLocation['driver']);

        return $clickLocation->toArray();
    }

    public static function locationUnknown(string $ip): array
    {
        return [
            'ip' => $ip,
        ];
    }
}

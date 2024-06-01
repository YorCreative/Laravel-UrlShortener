<?php

namespace YorCreative\UrlShortener\Utility\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use YorCreative\UrlShortener\Models\ShortUrlLocation;

/**
 * @extends Factory
 */
class ShortUrlLocationFactory extends Factory
{
    protected $model = ShortUrlLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'ip' => $this->faker->ipv4,
            'countryName' => $this->faker->country,
            'countryCode' => $this->faker->countryCode,
            'regionCode' => 'SR',
            'regionName' => 'Some Region',
            'cityName' => $this->faker->city,
            'zipCode' => $this->faker->postcode,
            'isoCode' => $this->faker->countryISOAlpha3,
            'postalCode' => $this->faker->postcode,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'metroCode' => $this->faker->citySuffix,
            'areaCode' => $this->faker->postcode,
            'timezone' => $this->faker->timezone,
        ];
    }
}

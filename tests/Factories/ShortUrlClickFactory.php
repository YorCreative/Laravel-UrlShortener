<?php

namespace YorCreative\UrlShortener\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Models\ShortUrlClick;
use YorCreative\UrlShortener\Models\ShortUrlLocation;
use YorCreative\UrlShortener\Services\ClickService;

/**
 * @extends Factory
 */
class ShortUrlClickFactory extends Factory
{
    protected $model = ShortUrlClick::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'short_url_id' => ShortUrl::factory(),
            'location_id' => ShortUrlLocation::factory(),
            'outcome_id' => ClickService::$SUCCESS_ROUTED,
        ];
    }
}

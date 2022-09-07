<?php

namespace YorCreative\UrlShortener\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Models\ShortUrlTracing;

/**
 * @extends Factory
 */
class ShortUrlTracingFactory extends Factory
{
    protected $model = ShortUrlTracing::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'short_url_id' => ShortUrl::factory(),
            'utm_id' => $this->faker->uuid,
            'utm_source' => 'linkedin',
            'utm_medium' => 'social',
            'utm_campaign' => 'sponsored_ad',
            'utm_term' => 'marketing+software',
            'utm_content' => 'xyz',
        ];
    }
}

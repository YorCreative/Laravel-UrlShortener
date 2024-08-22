<?php

namespace YorCreative\UrlShortener\Utility\Factories;

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
            'headers' => $headers = ['Accept' => 'application/json'],
            'headers_signature' => md5(json_encode($headers)),
        ];
    }

    public function withShortUrlId(int $short_url_id): self
    {
        return $this->state(function (array $attributes) use ($short_url_id) {
            return [
                'short_url_id' => $short_url_id,
            ];
        });
    }

    /**
     * @throws \Throwable
     */
    public function withSuccessOutcome(?int $outcome_id = null): self
    {
        is_null($outcome_id) ?: throw_if(! in_array($outcome_id, [1, 2]), 'Invalid successful outcome id');

        return $this->state(function (array $attributes) use ($outcome_id) {
            return [
                'outcome_id' => is_null($outcome_id) ? rand(1, 2) : $outcome_id,
            ];
        });
    }

    public function withFailureOutcome(?int $outcome_id = null): self
    {
        is_null($outcome_id) ?: throw_if(! in_array($outcome_id, [3, 4, 5, 6]), 'Invalid failure outcome id');

        return $this->state(function (array $attributes) use ($outcome_id) {
            return [
                'outcome_id' => is_null($outcome_id) ? rand(3, 6) : $outcome_id,
            ];
        });
    }
}

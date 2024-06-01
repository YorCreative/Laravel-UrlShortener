<?php

namespace YorCreative\UrlShortener\Utility\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Models\ShortUrlOwnership;

/**
 * @extends Factory
 */
class ShortUrlOwnershipFactory extends Factory
{
    protected $model = ShortUrlOwnership::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'short_url_id' => ShortUrl::factory(),
            'ownerable_type' => 'Tests\Models\DemoOwner',
            'ownerable_id' => 1,
        ];
    }

    public function withOwner(Model $model): self
    {
        return $this->state(function (array $attributes) use ($model) {
            return [
                'ownerable_type' => $model->getMorphClass(),
                'ownerable_id' => $model->getKey(),
            ];
        });
    }

    public function withShortUrlId(int $shortUrlId): self
    {
        return $this->state(function (array $attributes) use ($shortUrlId) {
            return [
                'short_url_id' => $shortUrlId,
            ];
        });
    }
}

<?php

namespace YorCreative\UrlShortener\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
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
            'short_url_id' => 1,
            'ownerable_type' => 'Tests\Models\DemoOwner',
            'ownerable_id' => 1,
        ];
    }
}

<?php

namespace YorCreative\UrlShortener\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use YorCreative\UrlShortener\Tests\Models\DemoOwner;

/**
 * @extends Factory
 */
class DemoOwnerFactory extends Factory
{
    protected $model = DemoOwner::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'email' => $this->faker->email,
            'name' => $this->faker->name,
        ];
    }
}

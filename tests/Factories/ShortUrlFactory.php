<?php

namespace YorCreative\UrlShortener\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

/**
 * @extends Factory
 */
class ShortUrlFactory extends Factory
{
    use ShortUrlHelper;

    protected $model = ShortUrl::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $plain_text = 'something-really-really-long.com/even/longer/thanks?ref=please&no=more&ref='.rand(4, 999999);

        return [
            'plain_text' => $plain_text,
            'hashed' => md5($plain_text),
            'identifier' => $this->generateUrlIdentifier(),
            'activation' => null,
            'expiration' => null,
            'password' => null,
            'limit' => null,
        ];
    }
}

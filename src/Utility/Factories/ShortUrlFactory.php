<?php

namespace YorCreative\UrlShortener\Utility\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Services\UtilityService;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

/**
 * @extends Factory
 */
class ShortUrlFactory extends Factory
{
    use ShortUrlHelper;

    protected $model = ShortUrl::class;

    protected static $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $plain_text = 'something-really-really-long.com/even/longer/thanks?ref=please&no=more&ref='.rand(4, 999999);

        return [
            'domain' => 'short.url',
            'plain_text' => $plain_text,
            'hashed' => md5($plain_text),
            'identifier' => $this->generateUrlIdentifier(),
            'activation' => null,
            'expiration' => Carbon::now()->addDays(60)->timestamp,
            'password' => null,
            'limit' => null,
        ];
    }

    public function withDomain(string $domain): self
    {
        return $this->state(function () use ($domain) {
            return [
                'domain' => $domain,
            ];
        });
    }

    public function withLimit(int $limit): self
    {
        return $this->state(function () use ($limit) {
            return [
                'limit' => $limit,
            ];
        });
    }

    public function withPassword(string $password): self
    {
        return $this->state(function () use ($password) {
            return [
                'password' => static::$password ??= UtilityService::getEncrypter()->encryptString($password),
            ];
        });
    }

    public function withExpiration(int $expiration_timestamp): self
    {
        return $this->state(function () use ($expiration_timestamp) {
            return [
                'expiration' => $expiration_timestamp,
            ];
        });
    }

    public function withLink(string $link): self
    {
        return $this->state(function () use ($link) {
            return [
                'plain_text' => $plain_text = ($link.$this->getDuplicateShortUrlQueryTag()),
                'hashed' => md5($plain_text),
            ];
        });
    }
}

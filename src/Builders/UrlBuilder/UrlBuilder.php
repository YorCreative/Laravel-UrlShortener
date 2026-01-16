<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\BaseOption;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\WithActivation;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\WithDomain;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\WithExpiration;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\WithOpenLimit;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\WithOwnership;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\WithPassword;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\WithTracing;
use YorCreative\UrlShortener\Exceptions\UrlBuilderException;
use YorCreative\UrlShortener\Exceptions\UrlServiceException;
use YorCreative\UrlShortener\Services\DomainResolver;
use YorCreative\UrlShortener\Services\UrlValidator;
use YorCreative\UrlShortener\Services\UtilityService;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

class UrlBuilder implements UrlBuilderInterface
{
    use ShortUrlHelper;

    private static UrlBuilder $builder;

    public Collection $shortUrlCollection;

    protected Collection $options;

    protected Collection $availableOptions;

    /**
     * UrlBuilder Constructor
     */
    public function __construct()
    {
        $this->options = new Collection;
        $this->shortUrlCollection = new Collection;
    }

    /**
     * Create a new short URL builder for the given URL.
     *
     * @throws UrlBuilderException If URL validation fails
     */
    public static function shorten(string $plain_text): UrlBuilder
    {
        // Validate URL for security (open redirect, SSRF prevention)
        UrlValidator::validate($plain_text);

        $b = self::$builder = new static;
        $b->shortUrlCollection->put('plain_text', $plain_text);
        $b->shortUrlCollection->put('hashed', md5($plain_text));

        $b->options->add(new BaseOption);

        return $b;
    }

    /**
     * @return $this
     *
     * @throws UrlBuilderException
     * @throws UrlServiceException
     */
    public function withPassword(string $password): UrlBuilder
    {
        $validator = Validator::make(
            ['password' => $password],
            [
                'password' => [
                    'required',
                    'string',
                    'min:'.(config('urlshortener.protection.pwd_req.min') ?? 6),
                    'max:'.(config('urlshortener.protection.pwd_req.max') ?? 32),
                ],
            ]
        );

        if ($validator->fails()) {
            throw new UrlBuilderException(
                'The password provided for the ShortUrl does not meet requirements: '.$validator->errors()->first()
            );
        }

        $this->shortUrlCollection->put('password', UtilityService::getEncrypter()->encryptString($password));

        $this->options->add(
            new WithPassword
        );

        return $this;
    }

    /**
     * @return $this
     *
     * @throws UrlBuilderException
     */
    public function withExpiration(int $timestamp): UrlBuilder
    {
        if ($timestamp <= 0) {
            throw new UrlBuilderException('Expiration timestamp must be a positive integer.');
        }

        if ($timestamp <= time()) {
            throw new UrlBuilderException('Expiration timestamp must be in the future.');
        }

        $activation = $this->shortUrlCollection->get('activation');
        if ($activation !== null && $timestamp <= $activation) {
            throw new UrlBuilderException('Expiration timestamp must be after activation timestamp.');
        }

        $this->shortUrlCollection->put('expiration', $timestamp);

        $this->options->add(
            new WithExpiration
        );

        return $this;
    }

    /**
     * @return $this
     *
     * @throws UrlBuilderException
     */
    public function withActivation(int $timestamp): UrlBuilder
    {
        if ($timestamp <= 0) {
            throw new UrlBuilderException('Activation timestamp must be a positive integer.');
        }

        $expiration = $this->shortUrlCollection->get('expiration');
        if ($expiration !== null && $timestamp >= $expiration) {
            throw new UrlBuilderException('Activation timestamp must be before expiration timestamp.');
        }

        $this->shortUrlCollection->put('activation', $timestamp);

        $this->options->add(
            new WithActivation
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function withOpenLimit(int $limit): UrlBuilder
    {
        $this->shortUrlCollection->put('limit', $limit);

        $this->options->add(
            new WithOpenLimit
        );

        return $this;
    }

    public function withOwnership(Model $model): UrlBuilder
    {
        $this->shortUrlCollection->put('owner_model', $model);

        $this->options->add(
            new WithOwnership
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function withTracing(array $utm_parameters)
    {
        $this->shortUrlCollection->put('utm_parameters', $utm_parameters);

        $this->options->add(
            new WithTracing
        );

        return $this;
    }

    /**
     * Set the domain for this short URL.
     *
     * @throws UrlBuilderException
     */
    public function forDomain(string $domain): UrlBuilder
    {
        $resolver = app(DomainResolver::class);

        if (! $resolver->isAllowed($domain)) {
            throw new UrlBuilderException("Domain '{$domain}' is not configured or not allowed.");
        }

        $this->shortUrlCollection->put('domain', $domain);

        $this->options->add(new WithDomain);

        return $this;
    }

    /**
     * Alias for forDomain() for fluent API.
     *
     * @throws UrlBuilderException
     */
    public function onDomain(string $domain): UrlBuilder
    {
        return $this->forDomain($domain);
    }

    /**
     * Use the current request's domain.
     *
     * @throws UrlBuilderException If domain validation is enabled and current domain is not allowed
     */
    public function forCurrentDomain(): UrlBuilder
    {
        $resolver = app(DomainResolver::class);
        $domain = $resolver->resolve();

        if ($domain) {
            // Validate domain is allowed when domain validation is enabled
            if (config('urlshortener.domains.validate_domain', true) && ! $resolver->isAllowed($domain)) {
                throw new UrlBuilderException("Current domain '{$domain}' is not configured or not allowed.");
            }

            $this->shortUrlCollection->put('domain', $domain);
            $this->options->add(new WithDomain);
        }

        return $this;
    }

    /**
     * Set custom prefix (overrides domain config).
     */
    public function withPrefix(string $prefix): UrlBuilder
    {
        $this->shortUrlCollection->put('custom_prefix', $prefix);

        return $this;
    }

    /**
     * Set custom identifier length (overrides domain config).
     *
     * @throws UrlBuilderException
     */
    public function withIdentifierLength(int $length): UrlBuilder
    {
        if ($length < 1) {
            throw new UrlBuilderException('Identifier length must be at least 1.');
        }

        if ($length > 255) {
            throw new UrlBuilderException('Identifier length cannot exceed 255 characters.');
        }

        $this->shortUrlCollection->put('identifier_length', $length);

        return $this;
    }

    /**
     * Build and persist the short URL.
     *
     * Uses database transaction with automatic deadlock retry for concurrent requests.
     * The BaseOption handles identifier collision retries internally.
     *
     * @throws Exception
     */
    public function build(): string
    {
        $shortUrlCollection = $this->shortUrlCollection;

        // Use transaction with deadlock retry (attempts = 3 by default)
        // This provides better isolation for concurrent URL creation
        DB::transaction(function () use (&$shortUrlCollection) {
            $this->getOptions()->each(function ($option) use (&$shortUrlCollection) {
                $option->resolve($shortUrlCollection);
            });
        }, 3); // 3 attempts for deadlock retries

        // Use domain-aware URL building if multi-domain is enabled
        $domain = $shortUrlCollection->get('domain');
        $identifier = $shortUrlCollection->get('identifier');

        if (config('urlshortener.domains.enabled', false)) {
            $resolver = app(DomainResolver::class);

            return $resolver->buildUrl($identifier, $domain);
        }

        return $this->builtShortUrl($identifier);
    }

    public function getOptions(): Collection
    {
        return $this->options;
    }
}

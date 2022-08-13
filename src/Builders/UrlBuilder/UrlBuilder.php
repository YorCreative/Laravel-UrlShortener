<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\BaseOption;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\WithActivation;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\WithExpiration;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\WithOpenLimit;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\WithOwnership;
use YorCreative\UrlShortener\Builders\UrlBuilder\Options\WithPassword;
use YorCreative\UrlShortener\Exceptions\UrlBuilderException;
use YorCreative\UrlShortener\Exceptions\UrlServiceException;
use YorCreative\UrlShortener\Services\UtilityService;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

class UrlBuilder implements UrlBuilderInterface
{
    use ShortUrlHelper;

    /**
     * @var UrlBuilder
     */
    private static UrlBuilder $builder;

    /**
     * @var Collection
     */
    public Collection $shortUrlCollection;

    /**
     * @var Collection
     */
    protected Collection $options;

    /**
     * @var Collection
     */
    protected Collection $availableOptions;

    /**
     * UrlBuilder Constructor
     */
    public function __construct()
    {
        $this->options = new Collection();
        $this->shortUrlCollection = new Collection();
    }

    /**
     * @param  string  $plain_text
     * @return UrlBuilder
     */
    public static function shorten(string $plain_text): UrlBuilder
    {
        $b = self::$builder = new static;
        $b->shortUrlCollection->put('plain_text', $plain_text);
        $b->shortUrlCollection->put('hashed', md5($plain_text));

        $b->options->add(new BaseOption());

        return $b;
    }

    /**
     * @param  string  $password
     * @return $this
     *
     * @throws UrlBuilderException
     * @throws UrlServiceException
     */
    public function withPassword(string $password): UrlBuilder
    {
        try {
            Validator::make(
                [
                    'password' => $password,
                ],
                [
                    'password' => [
                        'required',
                        'string',
                        'min:'.config('urlshortener.protection.pwd_req.min') ?? 6,
                        'max:'.config('urlshortener.protection.pwd_req.max') ?? 32,
                    ],
                ]
            );
        } catch (Exception $exception) {
            throw new UrlBuilderException('The password provided for the ShortUrl does not meet requirements.');
        }
        $this->shortUrlCollection->put('password', UtilityService::getEncrypter()->encryptString($password));

        $this->options->add(
            new WithPassword()
        );

        return $this;
    }

    /**
     * @param  int  $timestamp
     * @return $this
     */
    public function withExpiration(int $timestamp): UrlBuilder
    {
        $this->shortUrlCollection->put('expiration', $timestamp);

        $this->options->add(
            new WithExpiration()
        );

        return $this;
    }

    /**
     * @param  int  $timestamp
     * @return $this
     */
    public function withActivation(int $timestamp): UrlBuilder
    {
        $this->shortUrlCollection->put('activation', $timestamp);

        $this->options->add(
            new WithActivation()
        );

        return $this;
    }

    /**
     * @param  int  $limit
     * @return $this
     */
    public function withOpenLimit(int $limit): UrlBuilder
    {
        $this->shortUrlCollection->put('limit', $limit);

        $this->options->add(
            new WithOpenLimit()
        );

        return $this;
    }

    /**
     * @param  Model  $model
     * @return UrlBuilder
     */
    public function withOwnership(Model $model): UrlBuilder
    {
        $this->shortUrlCollection->put('owner_model', $model);

        $this->options->add(
            new WithOwnership()
        );

        return $this;
    }

    /**
     * @throws Exception
     */
    public function build(): string
    {
        $shortUrlCollection = $this->shortUrlCollection;

        DB::beginTransaction();

        try {
            $this->getOptions()->each(function ($option) use (&$shortUrlCollection) {
                $option->resolve($shortUrlCollection);
            });
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }

        DB::commit();

        return $this->builtShortUrl($shortUrlCollection->get('identifier'));
    }

    /**
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }
}

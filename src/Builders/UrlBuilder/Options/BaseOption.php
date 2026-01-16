<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder\Options;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilderOptionInterface;
use YorCreative\UrlShortener\Exceptions\UrlBuilderException;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Repositories\UrlRepository;
use YorCreative\UrlShortener\Services\DomainResolver;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

class BaseOption implements UrlBuilderOptionInterface
{
    use ShortUrlHelper;

    /**
     * Maximum number of retries for identifier collision.
     */
    protected const MAX_INSERT_RETRIES = 5;

    /**
     * @throws UrlBuilderException
     * @throws UrlRepositoryException
     */
    public function resolve(Collection &$shortUrlCollection): void
    {
        $domain = $shortUrlCollection->get('domain');
        $identifierLength = $shortUrlCollection->get('identifier_length');

        // Get identifier length from domain config if not specified
        if ($identifierLength === null && config('urlshortener.domains.enabled', false)) {
            $resolver = app(DomainResolver::class);
            $identifierLength = $resolver->getIdentifierLength($domain);
        }

        // Check hash exists for the specific domain (before attempting insert)
        if (UrlRepository::hashExists($shortUrlCollection->get('hashed'), $domain)) {
            $message = config('urlshortener.domains.enabled', false)
                ? 'A short url already exists for the long url provided on this domain.'
                : 'A short url already exists for the long url provided.';
            throw new UrlBuilderException($message);
        }

        // Attempt to create URL with retry logic for identifier collisions
        $this->createWithRetry($shortUrlCollection, $domain, $identifierLength);
    }

    /**
     * Create URL with retry logic to handle race conditions on identifier uniqueness.
     *
     * @throws UrlBuilderException
     * @throws UrlRepositoryException
     */
    protected function createWithRetry(Collection &$shortUrlCollection, ?string $domain, ?int $identifierLength): void
    {
        $createData = ['plain_text', 'hashed', 'identifier'];

        // Include domain if multi-domain is enabled
        if (config('urlshortener.domains.enabled', false)) {
            $createData[] = 'domain';
        }

        $lastException = null;

        for ($attempt = 0; $attempt < self::MAX_INSERT_RETRIES; $attempt++) {
            // Generate a new identifier for each attempt
            $identifier = $this->generateUrlIdentifier($domain, $identifierLength);

            $shortUrlCollection = $shortUrlCollection->merge([
                'identifier' => $identifier,
            ]);

            try {
                UrlRepository::create($shortUrlCollection->only($createData)->toArray());

                return; // Success - exit the retry loop
            } catch (UrlRepositoryException $e) {
                // Check if this is a duplicate key violation
                $previous = $e->getPrevious();
                if ($previous instanceof QueryException && $this->isDuplicateKeyError($previous)) {
                    // Identifier collision - retry with new identifier
                    $lastException = $e;

                    continue;
                }

                // Other database error - don't retry
                throw $e;
            }
        }

        // All retries exhausted
        throw new UrlBuilderException(
            'Unable to create short URL after '.self::MAX_INSERT_RETRIES.' attempts due to identifier collisions. Consider increasing identifier length.',
            0,
            $lastException
        );
    }

    /**
     * Check if the QueryException is a duplicate key violation.
     */
    protected function isDuplicateKeyError(QueryException $e): bool
    {
        $errorCode = $e->errorInfo[1] ?? null;

        // MySQL: 1062 = Duplicate entry
        // PostgreSQL: 23505 = unique_violation
        // SQLite: 19 = CONSTRAINT or 2067 = UNIQUE constraint failed
        return in_array($errorCode, [1062, 23505, 19, 2067], true)
            || str_contains($e->getMessage(), 'Duplicate entry')
            || str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || str_contains($e->getMessage(), 'unique_violation');
    }
}

<?php

namespace YorCreative\UrlShortener\Services;

use Exception;
use Illuminate\Support\Collection;
use Throwable;
use YorCreative\UrlShortener\Builders\ClickQueryBuilder\ClickQueryBuilder;
use YorCreative\UrlShortener\Exceptions\ClickServiceException;
use YorCreative\UrlShortener\Exceptions\FilterClicksStrategyException;
use YorCreative\UrlShortener\Models\ShortUrlClick;
use YorCreative\UrlShortener\Repositories\ClickRepository;
use YorCreative\UrlShortener\Repositories\LocationRepository;
use YorCreative\UrlShortener\Repositories\UrlRepository;
use YorCreative\UrlShortener\Strategies\FilterClicks\FilterClicksStrategy;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\BatchFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\IdentifierFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\OutcomeFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\OwnershipFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\StatusFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\TracingCampaignFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\TracingContentFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\TracingIdFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\TracingMediumFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\TracingSourceFilter;
use YorCreative\UrlShortener\Strategies\FilterClicks\Filters\TracingTermFilter;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

class ClickService
{
    use ShortUrlHelper;

    /**
     * @var int
     */
    public static int $SUCCESS_ROUTED = 1;

    /**
     * @var int
     */
    public static int $SUCCESS_PROTECTED = 2;

    /**
     * @var int
     */
    public static int $FAILURE_PROTECTED = 3;

    /**
     * @var int
     */
    public static int $FAILURE_LIMIT = 4;

    /**
     * @var int
     */
    public static int $FAILURE_EXPIRATION = 5;

    /**
     * @var int
     */
    public static int $FAILURE_ACTIVATION = 6;

    /**
     * @param  string  $identifier
     * @param  string  $request_ip
     * @param  int  $outcome_id
     * @param  bool  $test
     *
     * @throws ClickServiceException
     */
    public static function track(string $identifier, string $request_ip, int $outcome_id, bool $test = false): void
    {
        try {
            ClickRepository::createClick(
                UrlRepository::findByIdentifier($identifier)->id,
                LocationRepository::findOrCreateLocationRecord(
                    ! $test
                        ? LocationRepository::getLocationFrom($request_ip)
                        : LocationRepository::locationUnknown($request_ip)
                )->id,
                $outcome_id
            );
        } catch (Exception $exception) {
            throw new ClickServiceException($exception->getMessage());
        }
    }

    /**
     * @param  array  $filter
     * @return Collection
     *
     * @throws FilterClicksStrategyException
     * @throws Throwable
     */
    public static function get(array $filter = []): Collection
    {
        return self::handle(
            self::filterClickValidation($filter)
        );
    }

    /**
     * @param  array  $filterQuery
     * @return Collection
     *
     * @throws FilterClicksStrategyException
     */
    protected static function handle(array $filterQuery = []): Collection
    {
        $clickQueryBuilder = ClickService::getClickQueryBuilder();
        $filterStrategy = new FilterClicksStrategy();

        self::getFilters()->each(function ($filterObject) use ($filterQuery, &$filterStrategy) {
            if ($filterObject->canProcess($filterQuery)) {
                $filterStrategy->addFilter($filterObject);
            }
        });

        $filterStrategy->handle($clickQueryBuilder);

        return new Collection([
            'results' => $clickQueryBuilder->build(),
            'total' => $clickQueryBuilder->count(),
        ]);
    }

    /**
     * @return ClickQueryBuilder
     */
    public static function getClickQueryBuilder(): ClickQueryBuilder
    {
        return ShortUrlClick::query();
    }

    /**
     * @return Collection
     */
    protected static function getFilters(): Collection
    {
        return new Collection([
            new OutcomeFilter(),
            new BatchFilter(),
            new IdentifierFilter(),
            new StatusFilter(),
            new OwnershipFilter(),
            new TracingIdFilter(),
            new TracingCampaignFilter(),
            new TracingSourceFilter(),
            new TracingMediumFilter(),
            new TracingContentFilter(),
            new TracingTermFilter(),
        ]);
    }
}

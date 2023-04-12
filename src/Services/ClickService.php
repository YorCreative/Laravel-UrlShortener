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

    public static int $SUCCESS_ROUTED = 1;

    public static int $SUCCESS_PROTECTED = 2;

    public static int $FAILURE_PROTECTED = 3;

    public static int $FAILURE_LIMIT = 4;

    public static int $FAILURE_EXPIRATION = 5;

    public static int $FAILURE_ACTIVATION = 6;

    /**
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

    public static function getClickQueryBuilder(): ClickQueryBuilder
    {
        return ShortUrlClick::query();
    }

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

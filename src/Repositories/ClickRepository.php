<?php

namespace YorCreative\UrlShortener\Repositories;

use Exception;
use Illuminate\Support\Facades\DB;
use YorCreative\UrlShortener\Exceptions\ClickRepositoryException;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Models\ShortUrlClick;
use YorCreative\UrlShortener\Models\ShortUrlTracing;

class ClickRepository
{
    /**
     * @param  int  $id
     * @param  array  $with
     * @return ShortUrlClick
     *
     * @throws ClickRepositoryException
     */
    public static function findById(int $id, array $with = []): ShortUrlClick
    {
        try {
            return ShortUrlClick::where('id', $id)
                ->with(empty($with) ? self::defaultWithRelations() : $with)
                ->firstOrFail();
        } catch (Exception $exception) {
            throw new ClickRepositoryException($exception);
        }
    }

    /**
     * @param  int  $short_url_id
     * @param  int  $location_id
     * @param  int  $outcome_id
     * @param  int|null  $tracing_id
     *
     * @throws UrlRepositoryException
     */
    public static function createClick(int $short_url_id, int $location_id, int $outcome_id, ?int $tracing_id): void
    {
        try {
            ShortUrlClick::create([
                'short_url_id' => $short_url_id,
                'location_id' => $location_id,
                'outcome_id' => $outcome_id,
                'tracing_id' => $tracing_id,
            ]);
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    /**
     * @param  int  $short_url_id
     * @param  array  $trace
     *
     * @throws UrlRepositoryException
     */
    public static function createTrace(int $short_url_id, array $trace)
    {
        DB::beginTransaction();

        try {
            $traceRecord = ShortUrlTracing::query()
                ->create($trace);

            ShortUrlClick::query()
                ->where('short_url_id', $short_url_id)
                ->update([
                    'trace_id' => $traceRecord->id,
                ]);

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    /**
     * @return string[]
     */
    public static function defaultWithRelations(): array
    {
        return ['location', 'outcome', 'shortUrl', 'tracing'];
    }
}

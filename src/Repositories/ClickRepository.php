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
     * @throws UrlRepositoryException
     */
    public static function createClick(int $short_url_id, int $location_id, int $outcome_id): void
    {
        try {
            ShortUrlClick::create([
                'short_url_id' => $short_url_id,
                'location_id' => $location_id,
                'outcome_id' => $outcome_id,
            ]);
        } catch (Exception $exception) {
            throw new UrlRepositoryException($exception->getMessage());
        }
    }

    /**
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
        return ['location', 'outcome', 'shortUrl.tracing'];
    }
}

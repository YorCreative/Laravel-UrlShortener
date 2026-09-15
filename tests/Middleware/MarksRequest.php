<?php

namespace YorCreative\UrlShortener\Tests\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Records that it ran, so tests can prove configured middleware is actually
 * executed rather than merely attached to the route definition.
 */
class MarksRequest
{
    public static bool $ran = false;

    public static function reset(): void
    {
        self::$ran = false;
    }

    public function handle(Request $request, Closure $next)
    {
        self::$ran = true;

        return $next($request);
    }
}

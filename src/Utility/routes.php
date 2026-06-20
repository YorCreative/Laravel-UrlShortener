<?php

use Illuminate\Support\Facades\Route;
use YorCreative\UrlShortener\Actions\AttemptProtected;
use YorCreative\UrlShortener\Actions\ShortUrlRedirect;
use YorCreative\UrlShortener\Middleware\ResolveDomain;
use YorCreative\UrlShortener\Services\DomainResolver;

$prefixes = app(DomainResolver::class)->getRoutablePrefixes();
$multiDomainEnabled = config('urlshortener.domains.enabled', false);

// Determine middleware stack
$middleware = ['web'];
if ($multiDomainEnabled) {
    $middleware[] = ResolveDomain::class;
}

Route::middleware($middleware)->group(function () use ($prefixes) {
    foreach ($prefixes as $prefix) {
        Route::get(
            $prefix.'/{identifier}',
            ShortUrlRedirect::class
        );

        $protectedRoute = Route::post(
            $prefix.'/protected',
            AttemptProtected::class
        );

        if (! Route::has('urlshortener.attempt.protected')) {
            $protectedRoute->name('urlshortener.attempt.protected');
        }
    }
});

/*
 * Root-level routes (no prefix) for domains configured with `prefix => null`.
 *
 * A prefix-less `{identifier}` route cannot be registered globally without
 * hijacking every root path of the host application, so it is scoped per-domain
 * via Route::domain(). This is only meaningful for the `host` resolution
 * strategy, where the configured host key is the request Host header.
 */
if ($multiDomainEnabled
    && config('urlshortener.domains.resolution_strategy', 'host') === 'host'
) {
    foreach (config('urlshortener.domains.hosts', []) as $host => $hostConfig) {
        $hostPrefix = $hostConfig['prefix'] ?? null;

        if (is_string($hostPrefix) && trim($hostPrefix, '/') !== '') {
            continue; // Already served by the prefixed routes above.
        }

        Route::domain($host)->middleware($middleware)->group(function () {
            Route::get(
                '{identifier}',
                ShortUrlRedirect::class
            );

            $protectedRoute = Route::post(
                'protected',
                AttemptProtected::class
            );

            if (! Route::has('urlshortener.attempt.protected')) {
                $protectedRoute->name('urlshortener.attempt.protected');
            }
        });
    }
}

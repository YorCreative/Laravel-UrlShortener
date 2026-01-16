<?php

use Illuminate\Support\Facades\Route;
use YorCreative\UrlShortener\Actions\AttemptProtected;
use YorCreative\UrlShortener\Actions\ShortUrlRedirect;
use YorCreative\UrlShortener\Middleware\ResolveDomain;

$prefix = config('urlshortener.branding.prefix') ?? 'v1';
$multiDomainEnabled = config('urlshortener.domains.enabled', false);

// Determine middleware stack
$middleware = ['web'];
if ($multiDomainEnabled) {
    $middleware[] = ResolveDomain::class;
}

Route::middleware($middleware)->group(function () use ($prefix) {
    Route::get(
        $prefix.'/{identifier}',
        ShortUrlRedirect::class
    );

    Route::post(
        $prefix.'/protected',
        AttemptProtected::class
    )->name('urlshortener.attempt.protected');
});

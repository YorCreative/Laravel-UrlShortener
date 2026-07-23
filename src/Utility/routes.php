<?php

use Illuminate\Routing\Route;
use YorCreative\UrlShortener\Actions\AttemptProtected;
use YorCreative\UrlShortener\Actions\ShortUrlRedirect;

if (config('urlshortener.use_custom_routes')) {
    Route::middleware('web')->group(function () {
        Route::get(
            config('urlshortener.branding.prefix').'/{identifier}',
            ShortUrlRedirect::class
        );

        Route::post(
            config('urlshortener.branding.prefix').'/protected',
            AttemptProtected::class
        )->name('urlshortener.attempt.protected');
    });
}

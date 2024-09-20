<?php

use Illuminate\Routing\Route;

if(config('urlshortener.use_custom_routes')) {
    Route::middleware('web')->group(function () {
        Route::get(
            config('urlshortener.branding.prefix').'/{identifier}',
            YorCreative\UrlShortener\Actions\ShortUrlRedirect::class
        );

        Route::post(
            config('urlshortener.branding.prefix').'/protected',
            YorCreative\UrlShortener\Actions\AttemptProtected::class
        )->name('urlshortener.attempt.protected');
    });
}

<?php

namespace YorCreative\UrlShortener\Tests;

use YorCreative\UrlShortener\UrlShortenerServiceProvider;

class TestUrlShortenerServiceProvider extends UrlShortenerServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }
}

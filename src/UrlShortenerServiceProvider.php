<?php

namespace YorCreative\UrlShortener;

use Illuminate\Support\ServiceProvider;

class UrlShortenerServiceProvider extends ServiceProvider
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
        // todo
        // uncomment location config && update readme/docs on usage
        // needs testing on side affects of modifying location config.

        $this->mergeConfigFrom(__DIR__.'/Utility/Backpack/location.php', 'location');
        $this->mergeConfigFrom(__DIR__.'/Utility/Config/urlshortener.php', 'urlshortener');
        $this->loadMigrationsFrom(__DIR__.'/Utility/Migrations');
        $this->loadRoutesFrom(__DIR__.'/Utility/routes.php');
        $this->loadViewsFrom(__DIR__.'/Utility/Views', 'urlshortener');
        $this->publishes([
            __DIR__.'/Utility/Views' => base_path('resources/views/yorcreative/urlshortener'),
            __DIR__.'/Utility/Config' => base_path('config'),
        ]);
    }
}

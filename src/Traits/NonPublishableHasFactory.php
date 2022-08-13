<?php

namespace YorCreative\UrlShortener\Traits;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

trait NonPublishableHasFactory
{
    use HasFactory;

    /**
     * @return mixed
     */
    protected static function newFactory()
    {
        $package = Str::before(get_called_class(), 'Models\\');
        $modelName = Str::after(get_called_class(), 'Models\\');
        $path = $package.'Factories\\'.$modelName.'Factory';

        return $path::new();
    }
}

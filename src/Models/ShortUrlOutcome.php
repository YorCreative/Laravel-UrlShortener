<?php

namespace YorCreative\UrlShortener\Models;

use Illuminate\Database\Eloquent\Model;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

class ShortUrlOutcome extends Model
{
    use ShortUrlHelper;

    /**
     * @var bool
     */
    public $incrementing = true;

    /**
     * @var string
     */
    protected $table = 'short_url_outcomes';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'alias',
    ];
}

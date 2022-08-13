<?php

namespace YorCreative\UrlShortener\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use YorCreative\UrlShortener\Traits\NonPublishableHasFactory;
use YorCreative\UrlShortener\Traits\Ownerable;

class DemoOwner extends Model
{
    use NonPublishableHasFactory, Ownerable;

    /**
     * @var bool
     */
    public $incrementing = true;

    /**
     * @var string
     */
    protected $table = 'demo_owners';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string[]
     */
    protected $fillable = [
        'email',
        'name',
    ];
}

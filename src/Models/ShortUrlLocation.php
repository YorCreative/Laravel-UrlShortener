<?php

namespace YorCreative\UrlShortener\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use YorCreative\UrlShortener\Traits\PublishableHasFactory;

class ShortUrlLocation extends Model
{
    use PublishableHasFactory;

    /**
     * @var bool
     */
    public $incrementing = true;

    /**
     * @var string
     */
    protected $table = 'short_url_locations';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string[]
     */
    protected $fillable = [
        'ip',
        'countryName',
        'countryCode',
        'regionCode',
        'regionName',
        'cityName',
        'zipCode',
        'isoCode',
        'postalCode',
        'latitude',
        'longitude',
        'metroCode',
        'areaCode',
        'timezone',
    ];

    public function opens(): HasMany
    {
        return $this->hasMany(ShortUrlClick::class, 'location_id', 'id');
    }
}

<?php

namespace YorCreative\UrlShortener\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use YorCreative\UrlShortener\Traits\PublishableHasFactory;

class ShortUrlOwnership extends Model
{
    use PublishableHasFactory;

    /**
     * @var bool
     */
    public $incrementing = true;

    /**
     * @var string
     */
    protected $table = 'short_url_ownerships';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string[]
     */
    protected $fillable = [
        'short_url_id',
        'ownerable_id',
        'ownerable_type',
    ];

    public function shortUrl(): BelongsTo
    {
        return $this->belongsTo(ShortUrl::class, 'short_url_id', 'id');
    }
}

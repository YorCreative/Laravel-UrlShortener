
<br />
<br />

<div align="center">
  <a href="https://github.com/YorCreative">
    <img src="content/logo.png" alt="Logo" width="128" height="128">
  </a>
</div>

<h3 align="center">Laravel URL Shortener</h3>

<div align="center">
<a href="https://github.com/YorCreative/Laravel-UrlShortener/blob/main/LICENSE.md"><img alt="GitHub license" src="https://img.shields.io/github/license/YorCreative/Laravel-UrlShortener"></a>
<a href="https://github.com/YorCreative/Laravel-UrlShortener/stargazers"><img alt="GitHub stars" src="https://img.shields.io/github/stars/YorCreative/Laravel-UrlShortener"></a>
<a href="https://github.com/YorCreative/Laravel-UrlShortener/issues"><img alt="GitHub issues" src="https://img.shields.io/github/issues/YorCreative/Laravel-UrlShortener"></a>
<a href="https://github.com/YorCreative/Laravel-UrlShortener/network"><img alt="GitHub forks" src="https://img.shields.io/github/forks/YorCreative/Laravel-UrlShortener"></a>
<img alt="Packagist Downloads" src="https://img.shields.io/packagist/dt/YorCreative/Laravel-UrlShortener?color=green">
<a href="https://github.com/YorCreative/Laravel-UrlShortener/actions/workflows/phpunit.yml"><img alt="PHPUnit" src="https://github.com/YorCreative/Laravel-UrlShortener/actions/workflows/phpunit.yml/badge.svg"></a>
</div>

A Laravel URL Shortener package that provides URL redirects with optionally protected URL password, URL expiration, open
limits before expiration, ability to set feature activation dates, and click tracking out of the box for your Laravel
applications.

## Requirements

- PHP 8.1+
- Laravel 10.x, 11.x, or 12.x

## Installation

install the package via composer:

```bash
composer require yorcreative/laravel-urlshortener
```

Publish the packages assets.
```bash
php artisan vendor:publish --provider="YorCreative\UrlShortener\UrlShortenerServiceProvider"
```

Run migrations.
```bash
php artisan migrate
```

## Upgrade Guides

[Upgrading to v3.x from v2.x](https://github.com/YorCreative/Laravel-UrlShortener/wiki/Upgrading-to-3.x-from-2.x)

[Upgrading to v2.x from v1.x](https://github.com/YorCreative/Laravel-UrlShortener/wiki/Upgrading-to-2.x-from-1.x)

## Usage

Building Short Urls

```php
/**
 * Basic
 */
$url = UrlService::shorten('https://something-extremely-long.com/even/longer?ref=with&some=thingelselonger')
        ->build();
// http(s)://host/prefix/identifier

/**
 * Advanced
 */
$url = UrlService::shorten('https://something-extremely-long.com/even/longer?ref=with&some=thingelselonger')
        ->withActivation(Carbon::now()->addHour()->timestamp)
        ->withExpiration(Carbon::now()->addDay()->timestamp)
        ->withOpenLimit(2)
        ->withOwnership(Model::find(1))
        ->withPassword('password')
        ->withTracing([
            'utm_id' => 't123',
            'utm_campaign' => 'campaign_name',
            'utm_source' => 'linkedin',
            'utm_medium' => 'social',
        ])
        ->build();
// http(s)://host/prefix/identifier
```

Finding Existing Short Urls

```php
/**
 * Find a Short URL by its identifier
 */
$shortUrl = UrlService::findByIdentifier('identifier');
// returns instance of ShortUrl Model.


/**
 * Find a Short URL by its hashed signature
 */
$shortUrl = UrlService::findByHash(md5('long_url'));
// returns instance of ShortUrl Model.


/**
 * Find a Short URL by its plain text long url string
 */
$shortUrl = UrlService::findByPlainText('long_url');
// returns instance of ShortUrl Model.

/**
 * Find or Create - returns existing ShortUrl if found, or UrlBuilder for new creation
 * This is useful when you want to avoid exceptions for duplicate URLs
 */
$result = UrlService::findOrCreate('long_url');
// returns ShortUrl if exists, or UrlBuilder if new

// Usage example:
$result = UrlService::findOrCreate('https://example.com/my-long-url');
if ($result instanceof ShortUrl) {
    // URL already exists, use existing short URL
    $shortUrl = $result;
} else {
    // New URL, continue building with options
    $shortUrl = $result->withExpiration(Carbon::now()->addWeek()->timestamp)->build();
}

/**
 * Find shortUrls by UTM combinations.
 *
 * Note* This method only accepts the following array fields:
 *  - utm_id
 *  - utm_campaign
 *  - utm_source
 *  - utm_medium
 *  - utm_content
 *  - utm_term
 */
$shortUrlCollection = UrlService::findByUtmCombination([
    'utm_campaign' => 'alpha',
    'utm_source' => 'bravo',
    'utm_medium' => 'testing'
])
// returns an instance of Eloquent Collection of ShortUrl Models.
```

Getting Click Information

```php
$clicks = ClickService::get()->toArray();

dd($clicks);
[
    'results' => [
        [
            'id' => ...,
            'created_at' => ...,
            'short_url' => [
                'id' => ...,
                'identifier' => ...,
                'hashed' => ...,
                'plain_text' => ...,
                'limit' => ...,
                'tracing' => [
                    'id' => ...,
                    'utm_id' => ...,
                    'utm_source' => ...,
                    'utm_medium' => ...,
                    'utm_campaign' => ...,
                    'utm_content' => ...,
                    'utm_term' => ...,
                ]
                'created_at' => ...,
                'updated_at' => ...
            ],
            'location' => [
                'id' => ...,
                'ip' => ...,
                'countryName' => ...,
                'countryCode' => ...,
                'regionCode' => ...,
                'regionName' => ...,
                'cityName' => ...,
                'zipCode' => ...,
                'isoCode' => ...,
                'postalCode' => ...,
                'latitude' => ...,
                'longitude' => ...,
                'metroCode' => ...,
                'areaCode' => ...,
                'timezone' => ...,
                'created_at' => ...,
                'updated_at' => ...
            ],
            'outcome' => [
                'id' => ...,
                'name' => ...,
                'alias' => ...,
            ],
        ]
    ],
    'total' => 1
];
```

Getting Click Information and Filtering on Ownership

```php
$clicks = ClickService::get([
    'ownership' =>  [
        Model::find(1),
        Model::find(2)
    ]
]);
```


Filter on Outcome

```php
$clicks = ClickService::get([
    'outcome' => [
        1, // successful_routed
        2, // successful_protected
        3, // failure_password
        4, // failure_expiration
        5  // failure_limit
    ]
]);
```
Filter on the Click's YorShortUrl Status

```php
$clicks = ClickService::get([
    'status' => [
        'active',
        'expired',
        'expiring' // within 30 minutes of expiring
    ]
]);
```

Filtered on YorShortUrl Identifier(s)

```php
$clicks = ClickService::get([
    'identifier' => [
         'xyz',
         'yxz'
    ]
]);
```

Filtered Clicks by UTM parameter(s). These Can be filtered together or individually.
```php
$clicks = ClickService::get([
    'utm_id' => [
         'xyz',
         'yxz'
    ],
    'utm_source' => [
         'linkedin',
         'facebook'
    ],
    'utm_medium' => [
         'social'
    ],
    'utm_campaign' => [
         'sponsored',
         'affiliate'
    ],
    'utm_content' => [
         'xyz',
         'yxz'
    ],
    'utm_term' => [
         'marketing+software',
         'short+url'
    ],
]);
```

Iterate Through Results With Batches

```php
$clicks = ClickService::get([
    'limit' => 500
    'offset' => 1500
]);

$clicks->get('results');
$clicks->get('total');
```

Putting it all Together

```php
/**
 * Get the successfully routed clicks for all active short urls that are owned by Model IDs 1,2,3 and 4.
 * Set the offset of results by 1500 clicks and limit by the results by 500.
 */
$clicks = ClickService::get([
    'ownership' => Model::whereIn('id', [1,2,3,4])->get()->toArray(),
    'outcome' => [
        3 // successful_routed
    ],
    'status' => [
        'active'
    ],
    'utm_campaign' => [
        'awareness'
    ],
    'utm_source' => [
        'github'
    ],
    'limit' => 500
    'offset' => 1500
]);
```

## UTM Support

When creating a Short URL, the following UTM parameters are available to attach to the Short URL for advanced tracking of your Short Urls.

- utm_id
- utm_campaign
- utm_source
- utm_medium
- utm_content
- utm_term

UTM information is hidden in the Short URL identifier and clicks are filterable by UTM parameters.

## Multi-Domain Support

v3 introduces multi-domain support, allowing you to host short URLs on multiple domains with per-domain configuration.

### Enabling Multi-Domain

Set the environment variable or update your config:

```env
URL_SHORTENER_MULTI_DOMAIN=true
```

### Configuration

```php
// config/urlshortener.php
'domains' => [
    'enabled' => env('URL_SHORTENER_MULTI_DOMAIN', false),
    'default' => env('URL_SHORTENER_DEFAULT_DOMAIN', env('APP_URL')),
    'resolution_strategy' => 'host', // 'host', 'subdomain', or 'path'

    'hosts' => [
        'short.io' => [
            'prefix' => 's',
            'identifier_length' => 4,
            'redirect_code' => 301,
        ],
        'link.company.com' => [
            'prefix' => null, // No prefix
            'identifier_length' => 8,
        ],
    ],

    'aliases' => [
        'www.short.io' => 'short.io',
    ],
],
```

### Building Short URLs for Specific Domains

```php
// Specify domain explicitly
$url = UrlService::shorten('https://example.com/long-url')
    ->forDomain('short.io')
    ->build();
// Returns: https://short.io/s/abc123

// Use current request's domain
$url = UrlService::shorten('https://example.com/long-url')
    ->forCurrentDomain()
    ->build();

// Custom prefix override
$url = UrlService::shorten('https://example.com/long-url')
    ->forDomain('short.io')
    ->withPrefix('custom')
    ->build();

// Custom identifier length
$url = UrlService::shorten('https://example.com/long-url')
    ->forDomain('short.io')
    ->withIdentifierLength(8)
    ->build();
```

### Domain-Aware Lookups

```php
// Find by identifier on specific domain
$shortUrl = UrlService::findByIdentifier('abc123', 'short.io');

// Find all URLs for a domain
$shortUrls = UrlService::findByDomain('short.io');

// Find or create with domain
$result = UrlService::findOrCreate('https://example.com', 'short.io');
```

### Same Identifier on Different Domains

With multi-domain enabled, the same identifier can exist on different domains pointing to different URLs:

```php
// Both can coexist
UrlService::shorten('https://site-a.com')->forDomain('short.io')->build();
// https://short.io/s/abc123 -> https://site-a.com

UrlService::shorten('https://site-b.com')->forDomain('link.co')->build();
// https://link.co/abc123 -> https://site-b.com
```

## Security Features

### URL Validation

v3 includes built-in URL validation to prevent open redirect and SSRF attacks. This is disabled by default for backwards compatibility, but **recommended for new installations**.

```env
# Enable in .env (recommended)
URL_SHORTENER_VALIDATE_URLS=true
```

```php
// config/urlshortener.php
'url_validation' => [
    'enabled' => env('URL_SHORTENER_VALIDATE_URLS', false),
    'allowed_schemes' => ['http', 'https'],
    'block_private_ips' => env('URL_SHORTENER_BLOCK_PRIVATE_IPS', true),
    'blocked_hosts' => [
        // 'internal.company.com',
    ],
    'block_metadata_endpoints' => env('URL_SHORTENER_BLOCK_METADATA', true),
],
```

**Protected against:**
- `javascript:` protocol (XSS)
- `data:` protocol (XSS)
- `file:` protocol (local file access)
- Private IP ranges (SSRF)
- Cloud metadata endpoints (SSRF)
- Localhost redirects

### Rate Limiting for Password-Protected URLs

Brute-force protection is automatically enabled for password-protected short URLs:

```php
// config/urlshortener.php
'protection' => [
    'rate_limit' => [
        'max_attempts' => env('URL_SHORTENER_PASSWORD_MAX_ATTEMPTS', 5),
        'decay_minutes' => env('URL_SHORTENER_PASSWORD_DECAY_MINUTES', 1),
    ],
],
```

After exceeding the maximum attempts, users receive a `429 Too Many Requests` response with a `Retry-After` header.

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `URL_SHORTENER_MULTI_DOMAIN` | `false` | Enable multi-domain support |
| `URL_SHORTENER_DEFAULT_DOMAIN` | `APP_URL` | Default domain for short URLs |
| `URL_SHORTENER_RESOLUTION_STRATEGY` | `host` | How to resolve domain from request |
| `URL_SHORTENER_VALIDATE_DOMAIN` | `true` | Validate requests against configured domains |
| `URL_SHORTENER_DOMAINS_DATABASE` | `false` | Store domain config in database |
| `URL_SHORTENER_VALIDATE_URLS` | `false` | Enable URL validation (recommended) |
| `URL_SHORTENER_BLOCK_PRIVATE_IPS` | `true` | Block private/internal IPs |
| `URL_SHORTENER_BLOCK_METADATA` | `true` | Block cloud metadata endpoints |
| `URL_SHORTENER_PASSWORD_MAX_ATTEMPTS` | `5` | Max password attempts before rate limit |
| `URL_SHORTENER_PASSWORD_DECAY_MINUTES` | `1` | Minutes until rate limit resets |

## Testing

```bash
composer test
```

## Credits

- [Yorda](https://github.com/yordadev)
- [All Contributors](../../contributors)



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

## Usage

Building Short Urls

```php
/**
* Basic
 */
$url = UrlService::shorten('something-extremely-long.com/even/longer?ref=with&some=thingelselonger')
        ->build(); 
// http(s)://host/prefix/identifier;

/**
* Advanced
 */
$url = UrlService::shorten('something-extremely-long.com/even/longer?ref=with&some=thingelselonger')
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
// http(s)://host/prefix/identifier;
```

Finding Existing Short Urls

```php

/**
 * Find a Short URL by its identifier 
 */
$shortUrl = UrlService::findByIdentifier('identifier');

/**
 * Find a Short URL by its hashed signature
 */
$shortUrl = UrlService::findByHash(md5('long_url'));

/**
 * Find a Short URL by its plain text long url string 
 */
$shortUrl = UrlService::findByPlainText('long_url');

/**
 * Will return an instance of Models/ShortUrl or throw UrlRepository('Unable to locate Short URL')
 */
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

## Testing

```bash
composer test
```

## Credits

- [Yorda](https://github.com/yordadev)
- [All Contributors](../../contributors)


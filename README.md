
<br />
<br />

<div align="center">
  <a href="https://github.com/YorCreative">
    <img src="content/logo.png" alt="Logo" width="128" height="128">
  </a>
</div>

<h3 align="center">Laravel URL Shortener</h3>

A Laravel URL Shortener package that provides URL redirects with optional protected url passwords, url expirations, open
limits before expiration, ability to set feature activation dates and click tracking out of the box for your Laravel
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
        ->build();
// http(s)://host/prefix/identifier;
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
                'created_at' => ...,
                'updated_at' =>> ...
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
            ]
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

## Testing

```bash
composer test
```

## Credits

- [Yorda](https://github.com/yordadev)
- [All Contributors](../../contributors)


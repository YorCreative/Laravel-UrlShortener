# Upgrading to v3.x from v2.x

## Breaking Changes

- Minimum PHP version is now 8.1 (was 8.0)
- Dropped Laravel 8 and 9 support (both EOL)
- `withExpiration()` now validates timestamp must be positive and in the future
- `withActivation()` now validates timestamp must be positive and before expiration
- `UrlService::attempt()` now throws `RateLimitExceededException` after too many password attempts
- Password validation is now properly enforced (6-32 characters by default)

## New Features

- Laravel 11 and 12 support
- PHP 8.1 through 8.5 support
- **Multi-domain support** - Host multiple short URL domains with per-domain configuration
- **URL validation** - Prevents open redirect and SSRF vulnerabilities (opt-in)
- **Rate limiting** - Brute-force protection for password-protected URLs
- New `UrlService::findOrCreate()` method
- New `UrlService::findByDomain()` method

## Bug Fixes

- Fixed URL building logic (str_ends_with/str_replace argument order)
- Fixed configuration to use Laravel's `env()` helper instead of `getenv()`
- Fixed 404 handling for invalid short URL identifiers (previously returned 500)
- Fixed password validation operator precedence
- Improved password comparison security

## Migration Steps

### 1. Update Composer

```bash
composer require yorcreative/laravel-urlshortener:^3.0
```

### 2. Publish New Config

```bash
php artisan vendor:publish --provider="YorCreative\UrlShortener\UrlShortenerServiceProvider" --force
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Update Password Attempt Handling

If you use `UrlService::attempt()` for password-protected URLs, add exception handling:

```php
use YorCreative\UrlShortener\Exceptions\RateLimitExceededException;

try {
    $shortUrl = UrlService::attempt($identifier, $password);
} catch (RateLimitExceededException $e) {
    // Too many attempts - $e->getRetryAfter() returns seconds until retry allowed
    return response('Too many attempts. Try again later.', 429)
        ->header('Retry-After', $e->getRetryAfter());
}
```

### 5. Fix Timestamp Validation

Ensure expiration/activation timestamps are valid:

```php
// Before (v2 allowed invalid timestamps)
->withExpiration(0)                    // No longer allowed
->withExpiration(time() - 3600)        // No longer allowed (past)
->withActivation($futureTime)
->withExpiration($earlierTime)         // No longer allowed (activation >= expiration)

// After (v3 validates)
->withExpiration(time() + 86400)       // Must be positive and in future
->withActivation(time() + 3600)
->withExpiration(time() + 86400)       // Expiration must be after activation
```

### 6. Review New Config Options

New configuration sections in `config/urlshortener.php`:

```php
// Multi-domain support (disabled by default)
'domains' => [
    'enabled' => env('URL_SHORTENER_MULTI_DOMAIN', false),
    // ...
],

// URL validation (disabled by default, recommended to enable)
'url_validation' => [
    'enabled' => env('URL_SHORTENER_VALIDATE_URLS', false),
    // ...
],

// Rate limiting for password attempts
'protection' => [
    'rate_limit' => [
        'max_attempts' => env('URL_SHORTENER_PASSWORD_MAX_ATTEMPTS', 5),
        'decay_minutes' => env('URL_SHORTENER_PASSWORD_DECAY_MINUTES', 1),
    ],
],
```

## Optional: Enable New Security Features

### URL Validation (Recommended)

Prevents open redirect and SSRF attacks:

```env
URL_SHORTENER_VALIDATE_URLS=true
```

### Multi-Domain Support

Host short URLs on multiple domains:

```env
URL_SHORTENER_MULTI_DOMAIN=true
```

See the [README](https://github.com/YorCreative/Laravel-UrlShortener#multi-domain-support) for full multi-domain configuration.

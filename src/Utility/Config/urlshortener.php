<?php

return [
    'branding' => [
        'views' => [
            'protected' => [
                'images' => [
                    'image-1' => 'https://cdn-icons-png.flaticon.com/512/180/180954.png',
                ],
                'content' => [
                    'message' => 'Password Protected',
                    'title' => 'Whoa! A Password Protected Route!',
                ],
            ],
        ],
        'prefix' => 'something/pretty/cool',
        'host' => env('APP_URL'),
        'identifier' => [
            'length' => 6,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Domain Configuration
    |--------------------------------------------------------------------------
    |
    | Configure multiple domains for your short URLs. When enabled, each domain
    | can have its own prefix, identifier length, and redirect settings.
    | Same identifiers can exist on different domains pointing to different URLs.
    |
    */
    'domains' => [
        // Enable/disable multi-domain support (default: false for backwards compatibility)
        'enabled' => env('URL_SHORTENER_MULTI_DOMAIN', false),

        // Default domain (used when no domain specified in UrlBuilder)
        'default' => env('URL_SHORTENER_DEFAULT_DOMAIN', env('APP_URL')),

        // Domain resolution strategy: 'host' | 'subdomain' | 'path' | 'custom'
        'resolution_strategy' => env('URL_SHORTENER_RESOLUTION_STRATEGY', 'host'),

        // Per-domain configuration
        'hosts' => [
            // Example configurations:
            // 'short.io' => [
            //     'prefix' => 's',
            //     'identifier_length' => 4,
            //     'redirect_code' => 301,
            // ],
            // 'link.company.com' => [
            //     'prefix' => null, // No prefix - identifier at root
            //     'identifier_length' => 8,
            // ],
        ],

        // Subdomain pattern configuration (for 'subdomain' resolution strategy)
        'subdomain' => [
            'base_domain' => env('URL_SHORTENER_BASE_DOMAIN'),
        ],

        // Domain aliases (multiple domains pointing to same config)
        'aliases' => [
            // 'sho.rt' => 'short.io',
            // 'www.short.io' => 'short.io',
        ],

        // Store domain config in database (enables runtime domain management)
        'use_database' => env('URL_SHORTENER_DOMAINS_DATABASE', false),

        // Validate incoming requests against configured domains
        // When true: requests from unconfigured domains return 404
        // When false: allows any domain (useful for development)
        'validate_domain' => env('URL_SHORTENER_VALIDATE_DOMAIN', true),
    ],

    'protection' => [
        'pwd_req' => [
            'min' => 6,
            'max' => 32,
        ],
        // Rate limiting for password attempts (brute-force protection)
        'rate_limit' => [
            // Maximum password attempts before rate limiting kicks in
            'max_attempts' => env('URL_SHORTENER_PASSWORD_MAX_ATTEMPTS', 5),
            // Minutes until rate limit resets
            'decay_minutes' => env('URL_SHORTENER_PASSWORD_DECAY_MINUTES', 1),
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | URL Validation (Security)
    |--------------------------------------------------------------------------
    |
    | Configure validation for URLs being shortened. This prevents open redirect
    | and SSRF vulnerabilities by blocking dangerous protocols and internal IPs.
    |
    */
    'url_validation' => [
        // Enable URL validation (recommended for security)
        'enabled' => env('URL_SHORTENER_VALIDATE_URLS', false),

        // Allowed URL schemes (protocols)
        'allowed_schemes' => ['http', 'https'],

        // Block private/internal IP ranges (SSRF protection)
        'block_private_ips' => env('URL_SHORTENER_BLOCK_PRIVATE_IPS', true),

        // Additional blocked hosts (e.g., internal services)
        'blocked_hosts' => [
            // 'internal.company.com',
            // 'admin.local',
        ],

        // Block cloud metadata endpoints
        'block_metadata_endpoints' => env('URL_SHORTENER_BLOCK_METADATA', true),
    ],

    'redirect' => [
        'code' => 307,
        /**
         * 301: Moved Permanently
         * 302: Found / Moved Temporarily
         * 303: See Other
         * 304: Not Modified
         * 307: Temporary Redirect
         * 308: Permanent Redirect
         */
        'headers' => [
            'Referer' => env('APP_URL', 'localhost:1337'),
        ],
    ],
];

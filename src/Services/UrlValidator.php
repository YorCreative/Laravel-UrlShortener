<?php

namespace YorCreative\UrlShortener\Services;

use YorCreative\UrlShortener\Exceptions\UrlBuilderException;

class UrlValidator
{
    /**
     * Cloud metadata endpoints that should be blocked (SSRF protection).
     */
    protected const METADATA_ENDPOINTS = [
        '169.254.169.254',  // AWS, GCP, Azure metadata
        'metadata.google.internal',
        'metadata.gke.internal',
        '100.100.100.200',  // Alibaba Cloud metadata
        'fd00:ec2::254',    // AWS IPv6 metadata
    ];

    /**
     * Validate a URL for security concerns before shortening.
     *
     * @throws UrlBuilderException
     */
    public static function validate(string $url): void
    {
        if (! config('urlshortener.url_validation.enabled', true)) {
            return;
        }

        $parsed = parse_url($url);

        if ($parsed === false || ! isset($parsed['scheme'])) {
            throw new UrlBuilderException('Invalid URL format. URL must include scheme and host.');
        }

        // Validate scheme first (catches javascript:, data:, file:, etc.)
        self::validateScheme($parsed['scheme']);

        // After validating scheme, ensure host exists
        if (! isset($parsed['host'])) {
            throw new UrlBuilderException('Invalid URL format. URL must include scheme and host.');
        }

        self::validateHost($parsed['host']);
    }

    /**
     * Validate the URL scheme (protocol).
     *
     * @throws UrlBuilderException
     */
    protected static function validateScheme(string $scheme): void
    {
        $scheme = strtolower($scheme);
        $allowedSchemes = config('urlshortener.url_validation.allowed_schemes', ['http', 'https']);

        if (! in_array($scheme, $allowedSchemes, true)) {
            throw new UrlBuilderException(
                "URL scheme '{$scheme}' is not allowed. Allowed schemes: ".implode(', ', $allowedSchemes)
            );
        }
    }

    /**
     * Validate the host for security concerns.
     *
     * @throws UrlBuilderException
     */
    protected static function validateHost(string $host): void
    {
        $host = strtolower($host);

        // Check blocked hosts from config
        $blockedHosts = config('urlshortener.url_validation.blocked_hosts', []);
        if (in_array($host, $blockedHosts, true)) {
            throw new UrlBuilderException("Host '{$host}' is not allowed.");
        }

        // Check metadata endpoints
        if (config('urlshortener.url_validation.block_metadata_endpoints', true)) {
            if (in_array($host, self::METADATA_ENDPOINTS, true)) {
                throw new UrlBuilderException('Cloud metadata endpoints are blocked for security reasons.');
            }
        }

        // Check private IPs
        if (config('urlshortener.url_validation.block_private_ips', true)) {
            self::validateNotPrivateIp($host);
        }
    }

    /**
     * Check if host resolves to a private/internal IP address.
     *
     * @throws UrlBuilderException
     */
    protected static function validateNotPrivateIp(string $host): void
    {
        // Check if host is already an IP address
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (self::isPrivateIp($host)) {
                throw new UrlBuilderException('URLs pointing to private/internal IP addresses are not allowed.');
            }

            return;
        }

        // Common localhost aliases
        $localhostAliases = ['localhost', 'localhost.localdomain', '127.0.0.1', '::1'];
        if (in_array($host, $localhostAliases, true)) {
            throw new UrlBuilderException('URLs pointing to localhost are not allowed.');
        }

        // Check if hostname contains suspicious patterns
        if (preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.|127\.)/i', $host)) {
            throw new UrlBuilderException('URLs with IP-like hostnames are not allowed.');
        }
    }

    /**
     * Check if an IP address is private/internal.
     */
    protected static function isPrivateIp(string $ip): bool
    {
        // IPv4 private ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // Check if it's a private or reserved IP
            $result = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );

            return $result === false;
        }

        // IPv6 private ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $result = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            );

            return $result === false;
        }

        return false;
    }
}

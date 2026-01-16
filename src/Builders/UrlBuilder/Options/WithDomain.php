<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder\Options;

use Illuminate\Support\Collection;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilderOptionInterface;
use YorCreative\UrlShortener\Exceptions\UrlBuilderException;

class WithDomain implements UrlBuilderOptionInterface
{
    /**
     * Valid domain name pattern (RFC 1123 compliant with support for IDN).
     * Allows: letters, numbers, hyphens, dots
     * Must not start or end with hyphen or dot
     * Each label must be 1-63 characters, total max 253 characters.
     */
    protected const DOMAIN_PATTERN = '/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.[a-z0-9-]{1,63}(?<!-))*$/i';

    /**
     * Domain option - validates and normalizes domain value.
     * The actual domain is stored during BaseOption creation.
     *
     * @throws UrlBuilderException
     */
    public function resolve(Collection &$shortUrlCollection): void
    {
        $domain = $shortUrlCollection->get('domain');

        if ($domain) {
            // Normalize domain (lowercase, no protocol, no trailing slash)
            $domain = strtolower($domain);
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = rtrim($domain, '/');

            // Remove any path components (security: prevent path traversal)
            if (str_contains($domain, '/')) {
                $domain = explode('/', $domain)[0];
            }

            // Remove port if present
            if (str_contains($domain, ':')) {
                $domain = explode(':', $domain)[0];
            }

            // Validate domain format
            $this->validateDomainFormat($domain);

            $shortUrlCollection->put('domain', $domain);
        }
    }

    /**
     * Validate domain format to prevent invalid or malicious input.
     *
     * @throws UrlBuilderException
     */
    protected function validateDomainFormat(string $domain): void
    {
        // Check length constraints
        if (strlen($domain) > 253) {
            throw new UrlBuilderException('Domain name exceeds maximum length of 253 characters.');
        }

        if (strlen($domain) < 1) {
            throw new UrlBuilderException('Domain name cannot be empty.');
        }

        // Check for path traversal attempts
        if (str_contains($domain, '..') || str_contains($domain, './')) {
            throw new UrlBuilderException('Domain contains invalid characters (path traversal attempt detected).');
        }

        // Check for null bytes or control characters (security)
        if (preg_match('/[\x00-\x1f\x7f]/', $domain)) {
            throw new UrlBuilderException('Domain contains invalid control characters.');
        }

        // Validate against RFC 1123 pattern
        if (! preg_match(self::DOMAIN_PATTERN, $domain)) {
            throw new UrlBuilderException(
                "Invalid domain format: '{$domain}'. Domain must contain only letters, numbers, hyphens, and dots."
            );
        }

        // Validate each label length (max 63 characters per label)
        $labels = explode('.', $domain);
        foreach ($labels as $label) {
            if (strlen($label) > 63) {
                throw new UrlBuilderException('Domain label exceeds maximum length of 63 characters.');
            }
        }
    }
}

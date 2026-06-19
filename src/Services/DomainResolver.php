<?php

namespace YorCreative\UrlShortener\Services;

use Illuminate\Http\Request;
use YorCreative\UrlShortener\Contracts\DomainResolverInterface;
use YorCreative\UrlShortener\Exceptions\DomainResolutionException;
use YorCreative\UrlShortener\Models\ShortUrlDomain;

class DomainResolver implements DomainResolverInterface
{
    protected ?string $resolvedDomain = null;

    protected array $domainConfig = [];

    /**
     * Resolve the current domain from the request.
     */
    public function resolve(?Request $request = null): ?string
    {
        if (! config('urlshortener.domains.enabled', false)) {
            return null; // Single domain mode
        }

        $request = $request ?? request();
        $strategy = config('urlshortener.domains.resolution_strategy', 'host');

        $this->resolvedDomain = match ($strategy) {
            'host' => $this->resolveFromHost($request),
            'subdomain' => $this->resolveFromSubdomain($request),
            'path' => $this->resolveFromPath($request),
            'custom' => $this->resolveCustom($request),
            default => throw new DomainResolutionException("Unknown resolution strategy: {$strategy}"),
        };

        // Check for aliases
        $aliases = config('urlshortener.domains.aliases', []);
        if (isset($aliases[$this->resolvedDomain])) {
            $this->resolvedDomain = $aliases[$this->resolvedDomain];
        }

        return $this->resolvedDomain;
    }

    /**
     * Resolve domain from full host header.
     */
    protected function resolveFromHost(Request $request): string
    {
        $host = $request->getHost();

        // Remove port if present
        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }

        // Remove www. prefix if present
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }

    /**
     * Resolve domain from subdomain pattern.
     */
    protected function resolveFromSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $baseDomain = config('urlshortener.domains.subdomain.base_domain');

        if (! $baseDomain || ! str_ends_with($host, $baseDomain)) {
            return null;
        }

        $subdomain = str_replace('.'.$baseDomain, '', $host);

        return $subdomain ?: null;
    }

    /**
     * Resolve domain from URL path segment.
     */
    protected function resolveFromPath(Request $request): ?string
    {
        $segments = $request->segments();

        // First segment could be the domain identifier
        return $segments[0] ?? null;
    }

    /**
     * Allow custom resolution via callback.
     */
    protected function resolveCustom(Request $request): ?string
    {
        if (app()->bound('urlshortener.domain.resolver.custom')) {
            $resolver = app()->make('urlshortener.domain.resolver.custom');

            if (is_callable($resolver)) {
                return $resolver($request);
            }
        }

        return null;
    }

    /**
     * Get configuration for a specific domain.
     */
    public function getConfig(?string $domain = null): array
    {
        $domain = $domain ?? $this->resolvedDomain;

        // Check database first if enabled
        if (config('urlshortener.domains.use_database', false)) {
            $dbConfig = ShortUrlDomain::where('domain', $domain)->first();
            if ($dbConfig) {
                return array_merge(
                    $this->getDefaultConfig(),
                    $dbConfig->settings ?? [],
                    ['prefix' => $dbConfig->prefix]
                );
            }
        }

        // Check config file
        $configuredDomains = config('urlshortener.domains.hosts', []);

        if (isset($configuredDomains[$domain])) {
            return array_merge($this->getDefaultConfig(), $configuredDomains[$domain]);
        }

        return $this->getDefaultConfig();
    }

    /**
     * Get default configuration from branding section.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'prefix' => config('urlshortener.branding.prefix'),
            'host' => config('urlshortener.branding.host'),
            'identifier_length' => config('urlshortener.branding.identifier.length', 6),
            'redirect_code' => config('urlshortener.redirect.code', 307),
        ];
    }

    /**
     * Get the currently resolved domain.
     */
    public function current(): ?string
    {
        return $this->resolvedDomain;
    }

    /**
     * Check if a domain is configured/allowed.
     */
    public function isAllowed(?string $domain): bool
    {
        if (! config('urlshortener.domains.enabled', false)) {
            return true; // Single domain mode allows everything
        }

        if ($domain === null) {
            return true; // Default domain
        }

        // Check database
        if (config('urlshortener.domains.use_database', false)) {
            return ShortUrlDomain::where('domain', $domain)
                ->where('is_active', true)
                ->exists();
        }

        // Check config
        $configuredDomains = config('urlshortener.domains.hosts', []);
        $aliases = config('urlshortener.domains.aliases', []);

        return isset($configuredDomains[$domain]) || isset($aliases[$domain]);
    }

    /**
     * Get the prefix for a domain.
     */
    public function getPrefix(?string $domain = null): ?string
    {
        $config = $this->getConfig($domain);

        return $config['prefix'] ?? null;
    }

    /**
     * Get prefixes that the package should register as routes.
     *
     * @return list<string>
     */
    public function getRoutablePrefixes(?string $domain = null): array
    {
        $prefixes = [];
        $addPrefix = function ($prefix) use (&$prefixes): void {
            if (! is_string($prefix)) {
                return;
            }

            $prefix = trim($prefix, '/');

            if ($prefix === '') {
                return;
            }

            $prefixes[] = $prefix;
        };

        $addPrefix(config('urlshortener.branding.prefix') ?? 'v1');

        if ($domain !== null) {
            $addPrefix($this->getPrefix($domain));
        } elseif (config('urlshortener.domains.enabled', false)) {
            foreach (config('urlshortener.domains.hosts', []) as $config) {
                $addPrefix($config['prefix'] ?? null);
            }
        }

        foreach (config('urlshortener.routing.additional_prefixes', []) as $prefix) {
            $addPrefix($prefix);
        }

        return array_values(array_unique($prefixes));
    }

    /**
     * Get the identifier length for a domain.
     */
    public function getIdentifierLength(?string $domain = null): int
    {
        $config = $this->getConfig($domain);

        return $config['identifier_length'] ?? 6;
    }

    /**
     * Build the full URL for an identifier on a specific domain.
     *
     * @throws DomainResolutionException
     */
    public function buildUrl(string $identifier, ?string $domain = null): string
    {
        return $this->buildUrlWithPrefix($identifier, $domain);
    }

    /**
     * Build the full URL with a per-call prefix override.
     *
     * This is intentionally not part of DomainResolverInterface to avoid
     * breaking existing custom resolver implementations in minor releases.
     *
     * @throws DomainResolutionException
     */
    public function buildUrlWithPrefix(string $identifier, ?string $domain = null, ?string $prefixOverride = null): string
    {
        $domain = $domain ?? $this->resolvedDomain ?? config('urlshortener.domains.default');
        $config = $this->getConfig($domain);

        $host = $domain ?? $config['host'] ?? null;

        if (empty($host)) {
            // Fall back to APP_URL if no host can be determined
            $host = config('app.url') ?? config('urlshortener.branding.host') ?? 'localhost';
        }

        // Ensure host is a string
        $host = (string) $host;

        // Add protocol if missing
        if (! str_starts_with($host, 'http://') && ! str_starts_with($host, 'https://')) {
            $host = 'https://'.$host;
        }

        $host = str_ends_with($host, '/') ? $host : $host.'/';

        $prefix = $prefixOverride ?? ($config['prefix'] ?? null);

        if ($prefix === null) {
            return $host.$identifier;
        }

        $prefix = trim($prefix, '/');

        if ($prefix === '') {
            return $host.$identifier;
        }

        return $host.$prefix.'/'.$identifier;
    }

    /**
     * Set the resolved domain manually.
     */
    public function setDomain(?string $domain): self
    {
        $this->resolvedDomain = $domain;

        return $this;
    }
}

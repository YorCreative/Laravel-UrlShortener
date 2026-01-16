<?php

namespace YorCreative\UrlShortener\Contracts;

use Illuminate\Http\Request;

interface DomainResolverInterface
{
    /**
     * Resolve the current domain from the request.
     */
    public function resolve(?Request $request = null): ?string;

    /**
     * Get configuration for a specific domain.
     */
    public function getConfig(?string $domain = null): array;

    /**
     * Get the currently resolved domain.
     */
    public function current(): ?string;

    /**
     * Check if a domain is configured/allowed.
     */
    public function isAllowed(?string $domain): bool;

    /**
     * Get the prefix for a domain.
     */
    public function getPrefix(?string $domain = null): ?string;

    /**
     * Build the full URL for an identifier on a specific domain.
     */
    public function buildUrl(string $identifier, ?string $domain = null): string;
}

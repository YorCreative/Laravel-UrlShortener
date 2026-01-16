<?php

namespace YorCreative\UrlShortener\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use YorCreative\UrlShortener\Services\DomainResolver;

class ResolveDomain
{
    public function __construct(
        protected DomainResolver $domainResolver
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if multi-domain is not enabled
        if (! config('urlshortener.domains.enabled', false)) {
            return $next($request);
        }

        // Resolve and store the current domain
        $domain = $this->domainResolver->resolve($request);

        // Make domain available throughout the request
        $request->attributes->set('urlshortener_domain', $domain);

        // Optionally validate domain is allowed (only when validate_domain is enabled)
        if (config('urlshortener.domains.validate_domain', true) && $domain && ! $this->domainResolver->isAllowed($domain)) {
            abort(404, 'Domain not configured');
        }

        return $next($request);
    }
}

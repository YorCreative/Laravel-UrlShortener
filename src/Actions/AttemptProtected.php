<?php

namespace YorCreative\UrlShortener\Actions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use YorCreative\UrlShortener\Exceptions\RateLimitExceededException;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Services\ClickService;
use YorCreative\UrlShortener\Services\DomainResolver;
use YorCreative\UrlShortener\Services\UrlService;
use YorCreative\UrlShortener\Services\UtilityService;

class AttemptProtected extends Controller
{
    /**
     * @throws UrlRepositoryException
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'max:255'],
            'identifier' => ['required', 'string'],
        ]);

        // Get domain from request attributes (set by middleware) or resolve from request
        // Note: We intentionally do NOT trust user-provided domain input for security
        $domain = null;
        if (config('urlshortener.domains.enabled', false)) {
            $domain = $request->attributes->get('urlshortener_domain')
                ?? app(DomainResolver::class)->resolve($request);
        }

        try {
            $shortUrl = UrlService::attempt(
                $request->input('identifier'),
                $request->input('password'),
                $domain,
                $request->ip()
            );
        } catch (RateLimitExceededException $e) {
            // Too many password attempts - return 429 with Retry-After header
            return response($e->getMessage(), 429)
                ->header('Retry-After', $e->getRetryAfter());
        } catch (UrlRepositoryException $e) {
            // Identifier not found - log for debugging, return 404 to prevent enumeration
            Log::debug('URL shortener: identifier not found during password attempt', [
                'identifier' => $request->input('identifier'),
                'domain' => $domain,
                'exception' => $e->getMessage(),
            ]);
            abort(404);
        } catch (Exception $e) {
            // Decryption or other unexpected errors - log and fail gracefully as 404
            Log::warning('URL shortener: unexpected error during password attempt', [
                'identifier' => $request->input('identifier'),
                'domain' => $domain,
                'exception' => $e->getMessage(),
            ]);
            abort(404);
        }

        if (! $shortUrl) {
            /**
             * Attempt failed ---
             * Record the click and abort.
             */
            ClickService::track(
                $request->input('identifier'),
                $request->ip(),
                ClickService::$FAILURE_PROTECTED,
                false,
                $domain
            );

            abort(404);
        }

        /**
         * Attempt was successful ---
         * Record the click and route yor short url.
         */
        ClickService::track(
            $request->input('identifier'),
            $request->ip(),
            ClickService::$SUCCESS_ROUTED,
            false,
            $domain
        );

        return redirect()->away(
            $shortUrl->plain_text,
            UtilityService::getRedirectCode($domain),
            UtilityService::getRedirectHeaders($request, $domain)
        );
    }
}

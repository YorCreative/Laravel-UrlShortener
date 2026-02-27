<?php

namespace YorCreative\UrlShortener\Actions;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use YorCreative\UrlShortener\Events\ShortUrlExpired;
use YorCreative\UrlShortener\Exceptions\ClickServiceException;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Services\ClickService;
use YorCreative\UrlShortener\Services\DomainResolver;
use YorCreative\UrlShortener\Services\UrlService;
use YorCreative\UrlShortener\Services\UtilityService;

class ShortUrlRedirect extends Controller
{
    protected string $identifier;

    protected ?ShortUrl $shortUrl;

    protected ?string $domain = null;

    protected Request $request;

    /**
     * @throws ClickServiceException
     */
    public function __invoke(Request $request, string $identifier)
    {
        $this->request = $request;
        $this->identifier = $identifier;

        // Resolve domain from request attributes (set by middleware) or resolve directly
        if (config('urlshortener.domains.enabled', false)) {
            $this->domain = $request->attributes->get('urlshortener_domain');

            if ($this->domain === null) {
                $resolver = app(DomainResolver::class);
                $this->domain = $resolver->resolve($request);
            }
        }

        /**
         * Get Short URL Identifier & Validate (with domain)
         */
        try {
            $this->shortUrl = UrlService::findByIdentifier($this->identifier, $this->domain);
        } catch (UrlRepositoryException $e) {
            abort(404);
        }

        if (! $this->shortUrl) {
            abort(404);
        }

        /**
         * Activation Validation
         */
        $this->isShortUrlActivated();

        /**
         * Expiration Validation
         */
        $this->isShortUrlExpired();

        /**
         * If the url has a limit && the urls clicks
         * where successfully routed are greater than or equal to
         * the limit on the url
         */
        $this->canShortUrlBeOpened();

        /**
         * Protected Short URL Check
         */
        if ($this->shortUrl->hasPassword()) {
            /**
             * ShortUrl is Password Protected ---
             * Record the click and render the protected view.
             */
            ClickService::track(
                $this->identifier,
                $this->request->ip(),
                ClickService::$SUCCESS_PROTECTED,
                false,
                $this->domain
            );

            return view('yorcreative.urlshortener.protected', [
                'identifier' => $this->identifier,
                'domain' => $this->domain,
            ]);
        }

        /**
         * ShortUrl Successfully Routed ---
         * Record the click and route away.
         */
        ClickService::track(
            $this->identifier,
            $this->request->ip(),
            ClickService::$SUCCESS_ROUTED,
            false,
            $this->domain
        );

        return redirect()->away(
            $this->shortUrl->plain_text,
            UtilityService::getRedirectCode($this->domain),
            UtilityService::getRedirectHeaders($this->request, $this->domain)
        );
    }

    /**
     * @throws ClickServiceException
     */
    private function isShortUrlActivated()
    {
        /**
         * Activation Validation
         */
        if ($this->shortUrl->hasActivation()
            && Carbon::now()->lt(Carbon::parse($this->shortUrl->activation))
        ) {
            /**
             * ShortUrl is not active yet ---
             * Record the click and abort.
             */
            ClickService::track(
                $this->identifier,
                $this->request->ip(),
                ClickService::$FAILURE_ACTIVATION,
                false,
                $this->domain
            );

            abort(404);
        }
    }

    /**
     * @throws ClickServiceException
     */
    private function isShortUrlExpired()
    {
        if ($this->shortUrl->hasExpiration()
            && Carbon::now()->gte(Carbon::parse($this->shortUrl->expiration))
        ) {
            /**
             * ShortUrl Expired ---
             * Record the click and abort.
             */
            ClickService::track(
                $this->identifier,
                $this->request->ip(),
                ClickService::$FAILURE_EXPIRATION,
                false,
                $this->domain
            );

            try {
                ShortUrlExpired::dispatch($this->shortUrl, $this->identifier, $this->domain);
            } catch (\Throwable $e) {
                report($e);
            }

            abort(404);
        }
    }

    /**
     * @throws ClickServiceException
     */
    private function canShortUrlBeOpened()
    {
        if ($this->shortUrl->hasLimit()
            && $this->shortUrl->clicks()->where(
                'outcome_id',
                ClickService::$SUCCESS_ROUTED
            )->count() >= $this->shortUrl->limit
        ) {
            /**
             * ShortUrl Limit Reached ---
             * Record the click and abort.
             */
            ClickService::track(
                $this->identifier,
                $this->request->ip(),
                ClickService::$FAILURE_LIMIT,
                false,
                $this->domain
            );

            abort(404);
        }
    }
}

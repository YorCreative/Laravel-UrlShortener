<?php

namespace YorCreative\UrlShortener\Actions;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use YorCreative\UrlShortener\Exceptions\ClickServiceException;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Services\ClickService;
use YorCreative\UrlShortener\Services\UrlService;
use YorCreative\UrlShortener\Services\UtilityService;

class ShortUrlRedirect extends Controller
{
    protected string $identifier;

    protected ?ShortUrl $shortUrl;

    /**
     * @throws UrlRepositoryException
     * @throws ClickServiceException
     */
    public function __invoke(Request $request, string $identifier)
    {
        $this->identifier = $identifier;
        /**
         * Get Short URL Identifier & Validate
         */
        if (! $this->shortUrl = UrlService::findByIdentifier($this->identifier)) {
            /**
             * ShortUrl Identifier does not exists
             */

            return abort(404);
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
                request()->ip(),
                ClickService::$SUCCESS_PROTECTED
            );

            return view('yorcreative.urlshortener.protected', [
                'identifier' => $this->identifier,
            ]);
        }

        /**
         * ShortUrl Successfully Routed ---
         * Record the click and route away.
         */
        ClickService::track(
            $identifier,
            $request->ip(),
            ClickService::$SUCCESS_ROUTED
        );

        return redirect()->away(
            $this->shortUrl->plain_text,
            UtilityService::getRedirectCode(),
            UtilityService::getRedirectHeaders($request)
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
                request()->ip(),
                ClickService::$FAILURE_ACTIVATION
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
                request()->ip(),
                ClickService::$FAILURE_EXPIRATION
            );

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
                request()->ip(),
                ClickService::$FAILURE_LIMIT
            );

            abort(404);
        }
    }
}

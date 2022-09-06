<?php

namespace YorCreative\UrlShortener\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use YorCreative\UrlShortener\Exceptions\ClickServiceException;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Exceptions\UrlServiceException;
use YorCreative\UrlShortener\Services\ClickService;
use YorCreative\UrlShortener\Services\UrlService;
use YorCreative\UrlShortener\Services\UtilityService;

class AttemptProtected extends Controller
{
    /**
     * @param  Request  $request
     * @return RedirectResponse
     *
     * @throws UrlRepositoryException
     * @throws ClickServiceException
     * @throws UrlServiceException
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'max:255'],
            'identifier' => ['required', 'exists:yor_short_urls,identifier'],
        ], [
            'identifier.exists' => 'Unable to process the given request, please try again.',
        ]);

        if (! $shortUrl = UrlService::attempt(
            $request->input('identifier'),
            $request->input('password')
        )) {
            /**
             * Attempt failed ---
             * Record the click and abort.
             */
            ClickService::track(
                $request,
                ClickService::$FAILURE_PROTECTED
            );

            abort(404);
        }

        /**
         * Attempt was successful ---
         * Record the click and route yor short url.
         */
        ClickService::track(
            $request,
            ClickService::$SUCCESS_ROUTED
        );

        return redirect()->away(
            $shortUrl->plain_text,
            UtilityService::getRedirectCode(),
            UtilityService::getRedirectHeaders($request)
        );
    }
}

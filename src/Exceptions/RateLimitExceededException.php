<?php

namespace YorCreative\UrlShortener\Exceptions;

use Exception;
use Throwable;

class RateLimitExceededException extends Exception
{
    protected int $retryAfter;

    public function __construct(string $message = 'Too many password attempts', int $retryAfter = 60, int $code = 429, Throwable $previous = null)
    {
        $this->retryAfter = $retryAfter;
        parent::__construct($message, $code, $previous);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}

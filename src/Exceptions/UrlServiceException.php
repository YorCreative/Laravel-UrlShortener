<?php

namespace YorCreative\UrlShortener\Exceptions;

use Exception;
use Throwable;

class UrlServiceException extends Exception
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

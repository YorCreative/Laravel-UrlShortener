<?php

namespace YorCreative\UrlShortener\Traits;

/**
 * Routes the package's models at the connection named by
 * `urlshortener.database.connection`.
 *
 * Resolving the name on each call rather than assigning `$connection` at
 * construction keeps the binding late, so a connection configured after the
 * model is instantiated is still honoured.
 */
trait UsesConfiguredConnection
{
    public function getConnectionName()
    {
        return config('urlshortener.database.connection') ?? $this->connection;
    }
}

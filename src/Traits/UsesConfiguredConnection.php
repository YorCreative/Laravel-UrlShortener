<?php

namespace YorCreative\UrlShortener\Traits;

/**
 * Routes the package's models at the connection named by
 * `urlshortener.database.connection`.
 *
 * Resolving the name on each call rather than assigning `$connection` at
 * construction keeps the binding late, so a connection configured after the
 * model is instantiated is still honoured.
 *
 * The configured name is a default, not an override: an explicitly set
 * connection wins, so `Model::on()`, `setConnection()`, and factory
 * connection overrides continue to work. Delegating to the parent rather
 * than reading `$this->connection` directly keeps whatever handling the
 * framework applies to the property, such as Laravel 13's enum unwrapping.
 */
trait UsesConfiguredConnection
{
    public function getConnectionName()
    {
        return parent::getConnectionName() ?? config('urlshortener.database.connection');
    }
}

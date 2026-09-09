<?php

namespace YorCreative\UrlShortener\Traits;

use Illuminate\Database\Eloquent\Model;

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

    /**
     * The connection this model names in its own right, ignoring the package default.
     *
     * getConnectionName() deliberately cannot answer this: it folds the
     * configured default in, so it can never report "this model names no
     * connection of its own". Relationship inheritance needs exactly that
     * distinction.
     */
    public function getExplicitConnectionName(): ?string
    {
        return parent::getConnectionName();
    }

    /**
     * Give a related model the parent's connection, as Eloquent otherwise would.
     *
     * Eloquent's own implementation donates the parent connection only to a
     * related model that does not already name one:
     *
     *     if (! $instance->getConnectionName()) {
     *         $instance->setConnection($this->getConnectionName());
     *     }
     *
     * For a package model that guard is never false -- getConnectionName()
     * answers with the configured default -- so the inheritance step is skipped
     * and a parent explicitly placed on another connection loads and writes its
     * relations against the package default instead, silently crossing
     * databases. Testing whether the related model names a connection *of its
     * own* restores the framework's intent while keeping the precedence order:
     * an explicit connection wins, then the package default, then the
     * application default.
     *
     * @param  string  $class
     * @return Model
     */
    protected function newRelatedInstance($class)
    {
        return tap(new $class, function ($instance) {
            if (! $this->namesItsOwnConnection($instance)) {
                $instance->setConnection($this->getConnectionName());
            }
        });
    }

    /**
     * @param  string  $class
     * @return Model
     */
    protected function newRelatedThroughInstance($class)
    {
        return tap(new $class, function ($instance) {
            if (! $this->namesItsOwnConnection($instance)) {
                $instance->setConnection($this->getConnectionName());
            }
        });
    }

    /**
     * Whether a related model has a connection deliberately configured on it.
     *
     * A related model outside the package cannot report its explicit name, so
     * it falls back to the framework's own test.
     */
    protected function namesItsOwnConnection(object $instance): bool
    {
        return method_exists($instance, 'getExplicitConnectionName')
            ? $instance->getExplicitConnectionName() !== null
            : $instance->getConnectionName() !== null;
    }
}

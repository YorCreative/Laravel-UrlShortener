<?php

namespace YorCreative\UrlShortener\Tests\Unit\Models;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Models\ShortUrlClick;
use YorCreative\UrlShortener\Models\ShortUrlDomain;
use YorCreative\UrlShortener\Models\ShortUrlOwnership;
use YorCreative\UrlShortener\Models\ShortUrlTracing;
use YorCreative\UrlShortener\Tests\TestCase;

/**
 * Relationship connection inheritance across two independent databases.
 *
 * Eloquent hands a related model the parent's connection in
 * newRelatedInstance(), but only when the related model does not already name
 * one:
 *
 *     if (! $instance->getConnectionName()) {
 *         $instance->setConnection($this->getConnectionName());
 *     }
 *
 * UsesConfiguredConnection makes getConnectionName() fall back to the package
 * default, so for package models that guard is never false and the inheritance
 * step is skipped entirely. A parent explicitly put on another connection --
 * ShortUrl::on('replica') -- therefore loads and writes its relations against
 * the package default instead, silently crossing databases.
 */
class RelationshipConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.shortener' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'database.connections.replica' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        // Build the package schema on both databases so a cross-connection read
        // fails by returning the wrong rows, not by hitting a missing table.
        foreach (['shortener', 'replica'] as $connection) {
            config(['urlshortener.database.connection' => $connection]);

            foreach (glob(dirname(__DIR__, 3).'/src/Utility/Migrations/*.php') as $path) {
                $migration = include $path;
                $migration->up();
            }
        }

        // The package default for every test below.
        config(['urlshortener.database.connection' => 'shortener']);
    }

    private function makeShortUrlOn(string $connection, string $identifier): ShortUrl
    {
        $plainText = 'https://relations.test/'.$identifier;

        return ShortUrl::on($connection)->create([
            'plain_text' => $plainText,
            'hashed' => md5($plainText),
            'identifier' => $identifier,
        ]);
    }

    // -----------------------------------------------------------------
    // The related instance itself
    // -----------------------------------------------------------------

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function relations_of_an_explicitly_connected_parent_use_that_connection()
    {
        $shortUrl = $this->makeShortUrlOn('replica', 'rel'.rand(999, 999999));

        $this->assertSame('replica', $shortUrl->getConnectionName());

        foreach (['clicks', 'tracing', 'ownership', 'domainConfig'] as $relation) {
            $this->assertSame(
                'replica',
                $shortUrl->{$relation}()->getRelated()->getConnectionName(),
                "The [{$relation}] relation did not inherit the parent's explicit connection."
            );

            $this->assertSame(
                'replica',
                $shortUrl->{$relation}()->getQuery()->getConnection()->getName(),
                "The [{$relation}] query ran on the wrong connection."
            );
        }
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function relations_of_a_default_connected_parent_use_the_package_default()
    {
        $shortUrl = $this->makeShortUrlOn('shortener', 'def'.rand(999, 999999));

        foreach (['clicks', 'tracing', 'ownership', 'domainConfig'] as $relation) {
            $this->assertSame(
                'shortener',
                $shortUrl->{$relation}()->getRelated()->getConnectionName(),
                "The [{$relation}] relation left the package default."
            );
        }
    }

    // -----------------------------------------------------------------
    // Reads: lazy and eager, checked against real rows
    // -----------------------------------------------------------------

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function a_lazy_loaded_relation_reads_from_the_parents_connection_only()
    {
        $identifier = 'lazy'.rand(999, 999999);
        $shortUrl = $this->makeShortUrlOn('replica', $identifier);

        // A decoy row with the same short_url_id on the package default. If the
        // relation resolves against the wrong connection it will find this.
        DB::connection('shortener')->table('short_url_clicks')->insert([
            'short_url_id' => $shortUrl->id,
            'location_id' => 1,
            'outcome_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('replica')->table('short_url_clicks')->insert([
            'short_url_id' => $shortUrl->id,
            'location_id' => 1,
            'outcome_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('replica')->table('short_url_clicks')->insert([
            'short_url_id' => $shortUrl->id,
            'location_id' => 1,
            'outcome_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertCount(
            2,
            $shortUrl->clicks()->get(),
            'The lazy-loaded relation did not read from the replica connection.'
        );
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function an_eager_loaded_relation_reads_from_the_parents_connection_only()
    {
        $identifier = 'eager'.rand(999, 999999);
        $shortUrl = $this->makeShortUrlOn('replica', $identifier);

        DB::connection('shortener')->table('short_url_clicks')->insert([
            'short_url_id' => $shortUrl->id,
            'location_id' => 1,
            'outcome_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Two on the replica against one decoy, so the count alone distinguishes
        // which database answered.
        foreach ([1, 2] as $ignored) {
            DB::connection('replica')->table('short_url_clicks')->insert([
                'short_url_id' => $shortUrl->id,
                'location_id' => 1,
                'outcome_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $loaded = ShortUrl::on('replica')->with('clicks')->find($shortUrl->id);

        $this->assertNotNull($loaded);
        $this->assertCount(
            2,
            $loaded->clicks,
            'The eager-loaded relation did not read from the replica connection.'
        );
    }

    // -----------------------------------------------------------------
    // Writes through a relationship
    // -----------------------------------------------------------------

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function creating_through_a_relation_writes_to_the_parents_connection_only()
    {
        $identifier = 'write'.rand(999, 999999);
        $shortUrl = $this->makeShortUrlOn('replica', $identifier);

        $shortUrl->clicks()->create(['location_id' => 1, 'outcome_id' => 1]);

        $this->assertSame(
            1,
            DB::connection('replica')->table('short_url_clicks')
                ->where('short_url_id', $shortUrl->id)->count(),
            'The related row was not written to the replica connection.'
        );

        $this->assertSame(
            0,
            DB::connection('shortener')->table('short_url_clicks')
                ->where('short_url_id', $shortUrl->id)->count(),
            'The related row leaked onto the package default connection.'
        );
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function creating_a_has_one_through_a_relation_stays_on_the_parents_connection()
    {
        $identifier = 'hasone'.rand(999, 999999);
        $shortUrl = $this->makeShortUrlOn('replica', $identifier);

        $shortUrl->tracing()->create([
            'utm_id' => 'testing',
            'utm_campaign' => 'testing',
            'utm_source' => 'testing',
            'utm_medium' => 'testing',
            'utm_content' => 'testing',
            'utm_term' => 'testing',
        ]);

        $this->assertSame(
            1,
            DB::connection('replica')->table('short_url_tracings')
                ->where('short_url_id', $shortUrl->id)->count(),
            'The tracing row was not written to the replica connection.'
        );

        $this->assertSame(
            0,
            DB::connection('shortener')->table('short_url_tracings')
                ->where('short_url_id', $shortUrl->id)->count(),
            'The tracing row leaked onto the package default connection.'
        );
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function saving_a_separately_constructed_model_keeps_that_models_own_connection()
    {
        // Eloquent's save() persists whatever model it is handed, on that
        // model's connection -- it does not re-home it onto the parent's. A
        // model built outside the relation therefore resolves the package
        // default, and that must stay true: this is the seam that lets a
        // caller deliberately pin a related record elsewhere.
        $shortUrl = $this->makeShortUrlOn('replica', 'detach'.rand(999, 999999));

        $tracing = new ShortUrlTracing(['utm_id' => 'testing']);
        $this->assertSame('shortener', $tracing->getConnectionName());

        $shortUrl->tracing()->save($tracing);

        $this->assertSame(
            1,
            DB::connection('shortener')->table('short_url_tracings')
                ->where('short_url_id', $shortUrl->id)->count(),
            'A separately constructed model should persist on its own connection.'
        );

        // And pinning it explicitly sends it where the caller asked.
        $pinned = (new ShortUrlTracing(['utm_id' => 'pinned']))->setConnection('replica');
        $shortUrl->tracing()->save($pinned);

        $this->assertSame(
            1,
            DB::connection('replica')->table('short_url_tracings')
                ->where('short_url_id', $shortUrl->id)->count(),
            'An explicitly pinned related model should persist where it was pinned.'
        );
    }

    // -----------------------------------------------------------------
    // Deliberate per-model configuration must survive
    // -----------------------------------------------------------------

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function a_related_model_with_its_own_explicit_connection_is_not_overridden()
    {
        // Eloquent only donates the parent connection to a related model that
        // does not name one. A related model deliberately pinned elsewhere must
        // keep its own connection.
        $click = (new ShortUrlClick)->setConnection('shortener');

        $this->assertSame('shortener', $click->getConnectionName());

        $onReplica = ShortUrlClick::on('replica')->getModel();
        $this->assertSame('replica', $onReplica->getConnectionName());
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function model_on_still_outranks_the_configured_default()
    {
        foreach ([ShortUrl::class, ShortUrlClick::class, ShortUrlTracing::class, ShortUrlOwnership::class, ShortUrlDomain::class] as $model) {
            $this->assertSame(
                'replica',
                $model::on('replica')->getModel()->getConnectionName(),
                "[{$model}] ignored an explicit Model::on()."
            );

            $this->assertSame(
                'shortener',
                (new $model)->getConnectionName(),
                "[{$model}] did not fall back to the package default."
            );
        }
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function an_unconfigured_package_defers_to_the_application_default()
    {
        config(['urlshortener.database.connection' => null]);

        $this->assertNull((new ShortUrl)->getConnectionName());
        $this->assertNull((new ShortUrl)->clicks()->getRelated()->getConnectionName());
    }
}

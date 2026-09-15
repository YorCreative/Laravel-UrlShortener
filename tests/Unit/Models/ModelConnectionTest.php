<?php

namespace YorCreative\UrlShortener\Tests\Unit\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Models\ShortUrlClick;
use YorCreative\UrlShortener\Models\ShortUrlDomain;
use YorCreative\UrlShortener\Models\ShortUrlLocation;
use YorCreative\UrlShortener\Models\ShortUrlOutcome;
use YorCreative\UrlShortener\Models\ShortUrlOwnership;
use YorCreative\UrlShortener\Models\ShortUrlTracing;
use YorCreative\UrlShortener\Tests\TestCase;

class ModelConnectionTest extends TestCase
{
    public static function modelProvider(): array
    {
        return [
            [ShortUrl::class],
            [ShortUrlClick::class],
            [ShortUrlDomain::class],
            [ShortUrlLocation::class],
            [ShortUrlOutcome::class],
            [ShortUrlOwnership::class],
            [ShortUrlTracing::class],
        ];
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    #[DataProvider('modelProvider')]
    public function it_defers_to_the_application_default_connection_when_unconfigured(string $model)
    {
        $this->assertNull(config('urlshortener.database.connection'));

        $this->assertNull((new $model)->getConnectionName());
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    #[DataProvider('modelProvider')]
    public function it_uses_the_configured_connection(string $model)
    {
        config(['urlshortener.database.connection' => 'shortener']);

        $this->assertSame('shortener', (new $model)->getConnectionName());
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function it_resolves_a_real_connection_instance_from_the_configured_name()
    {
        config([
            'database.connections.shortener' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'urlshortener.database.connection' => 'shortener',
        ]);

        $this->assertSame('shortener', (new ShortUrl)->getConnection()->getName());
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function package_migrations_target_the_configured_connection()
    {
        config(['urlshortener.database.connection' => 'shortener']);

        $migrations = glob(dirname(__DIR__, 3).'/src/Utility/Migrations/*.php');

        $this->assertNotEmpty($migrations);

        foreach ($migrations as $path) {
            $migration = include $path;

            $this->assertSame(
                'shortener',
                $migration->getConnection(),
                basename($path).' does not honour the configured connection'
            );
        }
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function migrations_create_their_tables_on_the_configured_connection()
    {
        $this->useSecondaryConnection();

        $path = dirname(__DIR__, 3).'/src/Utility/Migrations/2022_08_03_193744_create_short_urls_table.php';
        $migration = include $path;
        $migration->up();

        $this->assertTrue(
            Schema::connection('shortener')->hasTable('short_urls'),
            'Migration did not create its table on the configured connection.'
        );
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function models_read_and_write_through_the_configured_connection()
    {
        $this->useSecondaryConnection();

        foreach (glob(dirname(__DIR__, 3).'/src/Utility/Migrations/*.php') as $path) {
            $migration = include $path;
            $migration->up();
        }

        $plainText = 'https://configured-connection.test/'.rand(999, 999999);

        $shortUrl = ShortUrl::create([
            'plain_text' => $plainText,
            'hashed' => md5($plainText),
            'identifier' => 'conn'.rand(999, 999999),
        ]);

        $this->assertSame('shortener', $shortUrl->getConnection()->getName());

        // Present on the configured connection...
        $this->assertNotNull(
            DB::connection('shortener')->table('short_urls')->find($shortUrl->id)
        );

        // ...and absent from the application default, proving the isolation.
        $this->assertNull(
            DB::connection('testbench')->table('short_urls')
                ->where('identifier', $shortUrl->identifier)->first()
        );
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function the_builder_transaction_wraps_the_configured_connection()
    {
        $this->useSecondaryConnection();

        foreach (glob(dirname(__DIR__, 3).'/src/Utility/Migrations/*.php') as $path) {
            $migration = include $path;
            $migration->up();
        }

        // The seed rows the builder depends on must have landed on the
        // configured connection, not the application default.
        $this->assertSame(
            6,
            DB::connection('shortener')->table('short_url_outcomes')->count()
        );

        $plainText = 'https://builder-on-connection.test/'.rand(999, 999999);
        $url = UrlBuilder::shorten($plainText)->build();

        $identifier = last(explode('/', rtrim($url, '/')));

        $this->assertNotNull(
            DB::connection('shortener')->table('short_urls')
                ->where('identifier', $identifier)->first(),
            'UrlBuilder did not persist to the configured connection.'
        );
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    public function the_builder_opens_its_transaction_on_the_configured_connection()
    {
        $this->useSecondaryConnection();

        foreach (glob(dirname(__DIR__, 3).'/src/Utility/Migrations/*.php') as $path) {
            $migration = include $path;
            $migration->up();
        }

        // Sampled mid-write: if the transaction were opened on the default
        // connection, the writes would still succeed here but would sit
        // outside it, so the level on the configured connection stays 0.
        $level = null;
        ShortUrl::creating(function () use (&$level) {
            $level = DB::connection('shortener')->transactionLevel();
        });

        try {
            UrlBuilder::shorten('https://txn-connection.test/'.rand(999, 999999))->build();
        } finally {
            ShortUrl::flushEventListeners();
        }

        $this->assertNotNull($level, 'The builder never created a ShortUrl.');
        $this->assertGreaterThan(
            0,
            $level,
            'The builder transaction was not open on the configured connection.'
        );
    }

    #[Test]
    #[Group('Unit')]
    #[Group('DatabaseConnection')]
    #[DataProvider('modelProvider')]
    public function an_explicitly_set_connection_outranks_the_configured_one(string $model)
    {
        config([
            'database.connections.replica' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'urlshortener.database.connection' => 'shortener',
        ]);

        // The configured connection is a default, not an override, so
        // Model::on() and setConnection() must still work.
        $this->assertSame(
            'replica',
            (new $model)->setConnection('replica')->getConnectionName()
        );

        $this->assertSame(
            'replica',
            $model::on('replica')->getModel()->getConnectionName()
        );
    }

    protected function useSecondaryConnection(): void
    {
        config([
            'database.connections.shortener' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'urlshortener.database.connection' => 'shortener',
        ]);
    }
}

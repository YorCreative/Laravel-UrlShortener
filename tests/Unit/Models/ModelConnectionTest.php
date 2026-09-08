<?php

namespace YorCreative\UrlShortener\Tests\Unit\Models;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
}

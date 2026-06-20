<?php

namespace YorCreative\UrlShortener\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use YorCreative\UrlShortener\Tests\TestCase;
use YorCreative\UrlShortener\Tests\TestUrlShortenerServiceProvider;

class UrlShortenerServiceProviderTest extends TestCase
{
    #[Test]
    #[Group('ServiceProvider')]
    public function it_registers_tagged_publish_groups()
    {
        $provider = TestUrlShortenerServiceProvider::class;

        $this->assertSame(
            [dirname(__DIR__, 2).'/src/Utility/Config/urlshortener.php' => config_path('urlshortener.php')],
            ServiceProvider::pathsToPublish($provider, 'urlshortener-config')
        );

        $this->assertSame(
            [dirname(__DIR__, 2).'/src/Utility/Views' => resource_path('views/yorcreative/urlshortener')],
            ServiceProvider::pathsToPublish($provider, 'urlshortener-views')
        );

        $this->assertSame(
            [dirname(__DIR__, 2).'/src/Utility/Migrations' => database_path('migrations')],
            ServiceProvider::pathsToPublish($provider, 'urlshortener-migrations')
        );
    }

    #[Test]
    #[Group('ServiceProvider')]
    public function it_keeps_existing_untagged_publish_paths()
    {
        $paths = ServiceProvider::pathsToPublish(TestUrlShortenerServiceProvider::class);

        $this->assertArrayHasKey(dirname(__DIR__, 2).'/src/Utility/Views', $paths);
        $this->assertArrayHasKey(dirname(__DIR__, 2).'/src/Utility/Config', $paths);
    }
}

<?php

namespace YorCreative\UrlShortener\Tests\Feature;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Tests\TestCase;

class CustomPrefixRouteTest extends TestCase
{
    #[Test]
    #[Group('Feature')]
    #[Group('CustomPrefix')]
    public function it_routes_registered_custom_prefix_urls()
    {
        config(['urlshortener.routing.additional_prefixes' => ['custom']]);

        require dirname(__DIR__, 2).'/src/Utility/routes.php';

        $plainText = 'https://custom-prefix-destination.com/'.rand(999, 999999);
        $url = UrlBuilder::shorten($plainText)
            ->withPrefix('custom')
            ->build();

        $identifier = $this->extractIdentifier($url);

        $this->get('/custom/'.$identifier)
            ->assertRedirect($plainText);
    }

    protected function extractIdentifier(string $url): string
    {
        $url = rtrim($url, '/');
        $parts = explode('/', $url);

        return end($parts);
    }
}

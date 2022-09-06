<?php

namespace YorCreative\UrlShortener\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Services\UrlService;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public string $plain_text;

    public string $base;

    public string $hashed;

    public string $identifier;

    public string $url;

    public Request $request;

    public ShortUrl $shortUrl;

    public function setUp(): void
    {
        parent::setUp();

        // additional setup
        $files = new Collection(File::files(dirname(__DIR__).'/src/Utility/Migrations'));
        $files = $files->merge(File::files(dirname(__DIR__).'/tests/Migrations'));

        $files->each(function ($file) {
            $file = pathinfo($file);
            $migration = include $file['dirname'].'/'.$file['basename'];
            $migration->up();
        });

        $this->base = 'localhost.test/v1/';
        $this->plain_text = $this->getPlainText();
        $this->hashed = md5($this->plain_text);

        $this->url = UrlService::shorten($this->plain_text)->build();
        $this->identifier = str_replace($this->base, '', $this->url);

        $this->shortUrl = UrlService::findByIdentifier($this->identifier);

        $this->request = Request::create('something-short.com/not-really');
        $this->changeRequestIp(
            $this->request,
            '98.38.110.238'
        );
    }

    /**
     * @param  array  $query
     * @return Request
     */
    public function buildClickRequest(array $query = []): Request
    {
        $query = array_merge($query, [
            'identifier' => $this->identifier,
        ]);

        return Request::create('xyz.com/xyz', 'GET', $query);
    }

    /**
     * @return string
     */
    public function getPlainText(): string
    {
        return 'http://something-really-really-long.com/even/longer/thanks?ref=please&no=more&x='.rand(0, 199999);
    }

    /**
     * @param  Request  $request
     * @param $location_ip
     */
    public function changeRequestIp(Request &$request, $location_ip)
    {
        $request->server->add(['REMOTE_ADDR' => $location_ip]);
    }

    protected function getPackageProviders($app)
    {
        return [
            TestUrlShortenerServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}

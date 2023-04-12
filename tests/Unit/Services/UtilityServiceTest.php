<?php

namespace YorCreative\UrlShortener\Tests\Unit\Services;

use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use YorCreative\UrlShortener\Exceptions\UtilityServiceException;
use YorCreative\UrlShortener\Services\UtilityService;
use YorCreative\UrlShortener\Tests\TestCase;

class UtilityServiceTest extends TestCase
{
    /**
     * @test
     *
     * @group UtilityService
     *
     * @throws UtilityServiceException
     */
    public function it_can_successfully_get_an_instance_of_the_encrypter()
    {
        $this->assertInstanceOf(Encrypter::class, UtilityService::getEncrypter());
    }

    /**
     * @test
     *
     * @group UtilityService
     */
    public function it_can_get_the_redirect_code()
    {
        $this->assertEquals(
            307,
            UtilityService::getRedirectCode()
        );
    }

    /**
     * @test
     *
     * @group UtilityService
     */
    public function it_can_get_redirect_headers()
    {
        $request = Request::create('something-short.com/not-really');

        $this->changeRequestIp(
            $request,
            '1.3.3.7'
        );

        $this->assertEquals(
            [
                'Referer' => 'localhost:1337',
                'X-Forwarded-For' => '1.3.3.7',
            ],
            UtilityService::getRedirectHeaders($request)
        );
    }

    /**
     * @test
     *
     * @group UrlRepository
     */
    public function it_can_construct_redirect_headers_with_dynamic_headers()
    {
        $this->assertEquals([
            'Referer' => 'localhost:1337',
            'test' => 'something',
        ], UtilityService::constructRedirectHeaders(['test' => 'something']));
    }

    /**
     * @test
     *
     * @group UrlRepository
     */
    public function it_can_construct_redirect_headers()
    {
        $this->assertEquals([
            'Referer' => 'localhost:1337',
        ], UtilityService::constructRedirectHeaders());
    }
}

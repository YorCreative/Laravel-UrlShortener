<?php

namespace YorCreative\UrlShortener\Tests\Unit\Services;

use YorCreative\UrlShortener\Exceptions\UrlBuilderException;
use YorCreative\UrlShortener\Services\UrlValidator;
use YorCreative\UrlShortener\Tests\TestCase;

class UrlValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Enable URL validation for these tests
        config(['urlshortener.url_validation.enabled' => true]);
    }

    /**
     * @test
     *
     * @group UrlValidator
     */
    public function it_allows_valid_https_urls()
    {
        $this->expectNotToPerformAssertions();

        UrlValidator::validate('https://example.com/path/to/page');
        UrlValidator::validate('https://subdomain.example.com');
        UrlValidator::validate('https://example.com:8080/page');
    }

    /**
     * @test
     *
     * @group UrlValidator
     */
    public function it_allows_valid_http_urls()
    {
        $this->expectNotToPerformAssertions();

        UrlValidator::validate('http://example.com/path/to/page');
    }

    /**
     * @test
     *
     * @group UrlValidator
     */
    public function it_rejects_urls_without_scheme()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('Invalid URL format');

        UrlValidator::validate('example.com/path');
    }

    /**
     * @test
     *
     * @group UrlValidator
     */
    public function it_rejects_javascript_protocol()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('not allowed');

        UrlValidator::validate('javascript:alert("XSS")');
    }

    /**
     * @test
     *
     * @group UrlValidator
     */
    public function it_rejects_data_protocol()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('not allowed');

        UrlValidator::validate('data:text/html,<script>alert("XSS")</script>');
    }

    /**
     * @test
     *
     * @group UrlValidator
     */
    public function it_rejects_file_protocol()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('not allowed');

        UrlValidator::validate('file:///etc/passwd');
    }

    /**
     * @test
     *
     * @group UrlValidator
     */
    public function it_rejects_localhost_urls()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('localhost');

        UrlValidator::validate('http://localhost/admin');
    }

    /**
     * @test
     *
     * @group UrlValidator
     */
    public function it_rejects_loopback_ip()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('private/internal IP');

        UrlValidator::validate('http://127.0.0.1/admin');
    }

    /**
     * @test
     *
     * @group UrlValidator
     */
    public function it_rejects_private_ip_ranges()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('private/internal IP');

        UrlValidator::validate('http://192.168.1.1/admin');
    }

    /**
     * @test
     *
     * @group UrlValidator
     */
    public function it_rejects_aws_metadata_endpoint()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('metadata endpoints are blocked');

        UrlValidator::validate('http://169.254.169.254/latest/meta-data/');
    }

    /**
     * @test
     *
     * @group UrlValidator
     */
    public function it_allows_urls_when_validation_disabled()
    {
        config(['urlshortener.url_validation.enabled' => false]);

        $this->expectNotToPerformAssertions();

        // These would normally fail but should pass when validation is disabled
        UrlValidator::validate('javascript:alert("XSS")');
        UrlValidator::validate('http://localhost/admin');
    }

    /**
     * @test
     *
     * @group UrlValidator
     */
    public function it_allows_private_ips_when_config_disabled()
    {
        config(['urlshortener.url_validation.block_private_ips' => false]);

        $this->expectNotToPerformAssertions();

        UrlValidator::validate('http://192.168.1.1/admin');
    }
}

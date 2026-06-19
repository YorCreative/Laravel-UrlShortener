<?php

namespace YorCreative\UrlShortener\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    #[Group('UrlValidator')]
    public function it_allows_valid_https_urls()
    {
        $this->expectNotToPerformAssertions();

        UrlValidator::validate('https://example.com/path/to/page');
        UrlValidator::validate('https://subdomain.example.com');
        UrlValidator::validate('https://example.com:8080/page');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_allows_valid_http_urls()
    {
        $this->expectNotToPerformAssertions();

        UrlValidator::validate('http://example.com/path/to/page');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_rejects_urls_without_scheme()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('Invalid URL format');

        UrlValidator::validate('example.com/path');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_rejects_javascript_protocol()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('not allowed');

        UrlValidator::validate('javascript:alert("XSS")');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_rejects_data_protocol()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('not allowed');

        UrlValidator::validate('data:text/html,<script>alert("XSS")</script>');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_rejects_file_protocol()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('not allowed');

        UrlValidator::validate('file:///etc/passwd');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_rejects_localhost_urls()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('localhost');

        UrlValidator::validate('http://localhost/admin');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_rejects_loopback_ip()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('private/internal IP');

        UrlValidator::validate('http://127.0.0.1/admin');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_rejects_private_ip_ranges()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('private/internal IP');

        UrlValidator::validate('http://192.168.1.1/admin');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_rejects_aws_metadata_endpoint()
    {
        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('metadata endpoints are blocked');

        UrlValidator::validate('http://169.254.169.254/latest/meta-data/');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_allows_urls_when_validation_disabled()
    {
        config(['urlshortener.url_validation.enabled' => false]);

        $this->expectNotToPerformAssertions();

        // These would normally fail but should pass when validation is disabled
        UrlValidator::validate('javascript:alert("XSS")');
        UrlValidator::validate('http://localhost/admin');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_allows_private_ips_when_config_disabled()
    {
        config(['urlshortener.url_validation.block_private_ips' => false]);

        $this->expectNotToPerformAssertions();

        UrlValidator::validate('http://192.168.1.1/admin');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_rejects_hosts_that_resolve_to_private_ips()
    {
        config(['urlshortener.url_validation.resolve_dns_private_ips' => true]);

        $validator = new class extends UrlValidator
        {
            public static function validateWithStubbedDns(string $url): void
            {
                self::validate($url);
            }

            protected static function resolveHostIps(string $host): array
            {
                return $host === 'example.com' ? ['10.0.0.5'] : [];
            }
        };

        $this->expectException(UrlBuilderException::class);
        $this->expectExceptionMessage('resolves to a private/internal IP');

        $validator::validateWithStubbedDns('https://example.com/resource');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_skips_dns_private_ip_checks_when_config_disabled()
    {
        config(['urlshortener.url_validation.resolve_dns_private_ips' => false]);

        $validator = new class extends UrlValidator
        {
            public static function validateWithStubbedDns(string $url): void
            {
                self::validate($url);
            }

            protected static function resolveHostIps(string $host): array
            {
                throw new \RuntimeException('DNS resolution should not be called.');
            }
        };

        $this->expectNotToPerformAssertions();

        $validator::validateWithStubbedDns('https://example.com/resource');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_does_not_resolve_dns_by_default()
    {
        $validator = new class extends UrlValidator
        {
            public static function validateWithStubbedDns(string $url): void
            {
                self::validate($url);
            }

            protected static function resolveHostIps(string $host): array
            {
                throw new \RuntimeException('DNS resolution should not be called.');
            }
        };

        $this->expectNotToPerformAssertions();

        $validator::validateWithStubbedDns('https://example.com/resource');
    }

    #[Test]
    #[Group('UrlValidator')]
    public function it_allows_hosts_when_dns_resolution_returns_no_ips()
    {
        config(['urlshortener.url_validation.resolve_dns_private_ips' => true]);

        $validator = new class extends UrlValidator
        {
            public static function validateWithStubbedDns(string $url): void
            {
                self::validate($url);
            }

            protected static function resolveHostIps(string $host): array
            {
                return [];
            }
        };

        $this->expectNotToPerformAssertions();

        $validator::validateWithStubbedDns('https://unresolved.example/resource');
    }
}

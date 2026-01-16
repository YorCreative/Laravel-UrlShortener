<?php

namespace YorCreative\UrlShortener\Tests\Unit\Services;

use Illuminate\Support\Facades\RateLimiter;
use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Exceptions\RateLimitExceededException;
use YorCreative\UrlShortener\Services\UrlService;
use YorCreative\UrlShortener\Tests\TestCase;

class RateLimitingTest extends TestCase
{
    protected string $protectedIdentifier;

    protected string $protectedPlainText;

    protected function setUp(): void
    {
        parent::setUp();

        $this->protectedPlainText = 'https://rate-limit-test.com/'.rand(999, 999999);

        $url = UrlBuilder::shorten($this->protectedPlainText)
            ->withPassword('testpass123')
            ->build();

        $parts = explode('/', rtrim($url, '/'));
        $this->protectedIdentifier = end($parts);
    }

    protected function tearDown(): void
    {
        // Clear rate limiter after each test
        RateLimiter::clear('urlshortener:password_attempt:127.0.0.1:'.$this->protectedIdentifier);
        parent::tearDown();
    }

    /**
     * @test
     *
     * @group RateLimiting
     */
    public function it_allows_successful_attempts_without_rate_limiting()
    {
        $result = UrlService::attempt($this->protectedIdentifier, 'testpass123', null, '127.0.0.1');

        $this->assertNotNull($result);
        $this->assertEquals($this->protectedPlainText, $result->plain_text);
    }

    /**
     * @test
     *
     * @group RateLimiting
     */
    public function it_rate_limits_after_max_failed_attempts()
    {
        config(['urlshortener.protection.rate_limit.max_attempts' => 3]);
        config(['urlshortener.protection.rate_limit.decay_minutes' => 1]);

        // Make 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            $result = UrlService::attempt($this->protectedIdentifier, 'wrongpassword', null, '127.0.0.1');
            $this->assertNull($result);
        }

        // The 4th attempt should throw RateLimitExceededException
        $this->expectException(RateLimitExceededException::class);
        UrlService::attempt($this->protectedIdentifier, 'wrongpassword', null, '127.0.0.1');
    }

    /**
     * @test
     *
     * @group RateLimiting
     */
    public function it_clears_rate_limit_on_successful_attempt()
    {
        config(['urlshortener.protection.rate_limit.max_attempts' => 5]);

        // Make 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            UrlService::attempt($this->protectedIdentifier, 'wrongpassword', null, '127.0.0.1');
        }

        // Successful attempt should clear rate limit
        $result = UrlService::attempt($this->protectedIdentifier, 'testpass123', null, '127.0.0.1');
        $this->assertNotNull($result);

        // Now we should be able to make more failed attempts without hitting limit
        for ($i = 0; $i < 3; $i++) {
            $result = UrlService::attempt($this->protectedIdentifier, 'wrongpassword', null, '127.0.0.1');
            $this->assertNull($result);
        }
    }

    /**
     * @test
     *
     * @group RateLimiting
     */
    public function it_tracks_rate_limit_per_ip_and_identifier()
    {
        config(['urlshortener.protection.rate_limit.max_attempts' => 2]);

        // Make 2 failed attempts from IP1
        for ($i = 0; $i < 2; $i++) {
            UrlService::attempt($this->protectedIdentifier, 'wrongpassword', null, '192.168.1.1');
        }

        // IP1 should be rate limited
        try {
            UrlService::attempt($this->protectedIdentifier, 'wrongpassword', null, '192.168.1.1');
            $this->fail('Expected RateLimitExceededException');
        } catch (RateLimitExceededException $e) {
            $this->assertTrue(true);
        }

        // But IP2 should still be allowed
        $result = UrlService::attempt($this->protectedIdentifier, 'wrongpassword', null, '192.168.1.2');
        $this->assertNull($result); // Wrong password, but not rate limited

        // Cleanup
        RateLimiter::clear('urlshortener:password_attempt:192.168.1.1:'.$this->protectedIdentifier);
        RateLimiter::clear('urlshortener:password_attempt:192.168.1.2:'.$this->protectedIdentifier);
    }

    /**
     * @test
     *
     * @group RateLimiting
     */
    public function it_provides_retry_after_seconds()
    {
        config(['urlshortener.protection.rate_limit.max_attempts' => 1]);
        config(['urlshortener.protection.rate_limit.decay_minutes' => 2]);

        // Make 1 failed attempt to trigger rate limit
        UrlService::attempt($this->protectedIdentifier, 'wrongpassword', null, '127.0.0.1');

        try {
            UrlService::attempt($this->protectedIdentifier, 'wrongpassword', null, '127.0.0.1');
            $this->fail('Expected RateLimitExceededException');
        } catch (RateLimitExceededException $e) {
            $retryAfter = $e->getRetryAfter();
            // Should be around 120 seconds (2 minutes), allow some tolerance
            $this->assertGreaterThan(100, $retryAfter);
            $this->assertLessThanOrEqual(120, $retryAfter);
        }
    }
}

<?php

namespace YorCreative\UrlShortener\Tests\Feature;

use YorCreative\UrlShortener\Builders\UrlBuilder\UrlBuilder;
use YorCreative\UrlShortener\Services\ClickService;
use YorCreative\UrlShortener\Tests\TestCase;

class AttemptProtectedTest extends TestCase
{
    protected string $protectedIdentifier;

    protected string $protectedPlainText;

    protected function setUp(): void
    {
        parent::setUp();

        $this->protectedPlainText = 'https://protected-destination.com/'.rand(999, 999999);

        $url = UrlBuilder::shorten($this->protectedPlainText)
            ->withPassword('secret123')
            ->build();

        $this->protectedIdentifier = $this->extractIdentifier($url);
    }

    /**
     * @test
     *
     * @group Feature
     * @group AttemptProtected
     */
    public function it_redirects_with_correct_password()
    {
        $response = $this->post(route('urlshortener.attempt.protected'), [
            'identifier' => $this->protectedIdentifier,
            'password' => 'secret123',
        ]);

        $response->assertRedirect($this->protectedPlainText);

        // Verify click was tracked with SUCCESS_ROUTED
        $this->assertDatabaseHas('short_url_clicks', [
            'outcome_id' => ClickService::$SUCCESS_ROUTED,
        ]);
    }

    /**
     * @test
     *
     * @group Feature
     * @group AttemptProtected
     */
    public function it_returns_404_with_wrong_password()
    {
        $response = $this->post(route('urlshortener.attempt.protected'), [
            'identifier' => $this->protectedIdentifier,
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(404);

        // Verify click was tracked with FAILURE_PROTECTED
        $this->assertDatabaseHas('short_url_clicks', [
            'outcome_id' => ClickService::$FAILURE_PROTECTED,
        ]);
    }

    /**
     * @test
     *
     * @group Feature
     * @group AttemptProtected
     */
    public function it_validates_required_identifier()
    {
        $response = $this->post(route('urlshortener.attempt.protected'), [
            'password' => 'secret123',
        ]);

        $response->assertStatus(302); // Validation redirect
        $response->assertSessionHasErrors('identifier');
    }

    /**
     * @test
     *
     * @group Feature
     * @group AttemptProtected
     */
    public function it_validates_required_password()
    {
        $response = $this->post(route('urlshortener.attempt.protected'), [
            'identifier' => $this->protectedIdentifier,
        ]);

        $response->assertStatus(302); // Validation redirect
        $response->assertSessionHasErrors('password');
    }

    /**
     * @test
     *
     * @group Feature
     * @group AttemptProtected
     */
    public function it_returns_404_for_nonexistent_identifier()
    {
        $response = $this->post(route('urlshortener.attempt.protected'), [
            'identifier' => 'nonexistent_'.rand(999, 999999),
            'password' => 'anypassword',
        ]);

        $response->assertStatus(404);
    }

    /**
     * @test
     *
     * @group Feature
     * @group AttemptProtected
     */
    public function it_works_when_multidomain_is_disabled()
    {
        // Test that protected URL password check works correctly
        // when multi-domain mode is disabled (single domain mode)
        config(['urlshortener.domains.enabled' => false]);

        $plainText = 'https://single-domain-protected.com/'.rand(999, 999999);
        $url = UrlBuilder::shorten($plainText)
            ->withPassword('singlepass')
            ->build();

        $identifier = $this->extractIdentifier($url);

        // Should successfully redirect with correct password
        $response = $this->post(route('urlshortener.attempt.protected'), [
            'identifier' => $identifier,
            'password' => 'singlepass',
        ]);

        $response->assertRedirect($plainText);

        // Verify click was tracked
        $this->assertDatabaseHas('short_url_clicks', [
            'outcome_id' => ClickService::$SUCCESS_ROUTED,
        ]);
    }

    /**
     * Extract identifier from a built URL.
     */
    protected function extractIdentifier(string $url): string
    {
        $url = rtrim($url, '/');
        $parts = explode('/', $url);

        return end($parts);
    }
}

<?php

namespace YorCreative\UrlShortener\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use YorCreative\UrlShortener\Exceptions\UrlRepositoryException;
use YorCreative\UrlShortener\Services\UrlService;
use YorCreative\UrlShortener\Tests\TestCase;

class UrlServiceDeleteRestoreTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     *
     * @group UrlService
     */
    public function it_can_soft_delete_a_short_url_by_identifier()
    {
        $this->assertDatabaseHas('short_urls', [
            'identifier' => $this->identifier,
        ]);

        $result = UrlService::delete($this->identifier);

        $this->assertTrue($result);
        $this->assertSoftDeleted('short_urls', [
            'identifier' => $this->identifier,
        ]);
    }

    /**
     * @test
     *
     * @group UrlService
     */
    public function it_can_restore_a_soft_deleted_short_url()
    {
        UrlService::delete($this->identifier);

        $this->assertSoftDeleted('short_urls', [
            'identifier' => $this->identifier,
        ]);

        $result = UrlService::restore($this->identifier);

        $this->assertTrue($result);
        $this->assertNotSoftDeleted('short_urls', [
            'identifier' => $this->identifier,
        ]);
    }

    /**
     * @test
     *
     * @group UrlService
     */
    public function it_throws_exception_when_deleting_nonexistent_identifier()
    {
        $this->expectException(UrlRepositoryException::class);

        UrlService::delete('nonexistent_identifier_'.rand(999, 999999));
    }

    /**
     * @test
     *
     * @group UrlService
     */
    public function it_throws_exception_when_restoring_nonexistent_identifier()
    {
        $this->expectException(UrlRepositoryException::class);

        UrlService::restore('nonexistent_identifier_'.rand(999, 999999));
    }
}

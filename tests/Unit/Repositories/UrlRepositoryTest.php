<?php

namespace YorCreative\UrlShortener\Tests\Unit\Repositories;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use YorCreative\UrlShortener\Models\ShortUrlOwnership;
use YorCreative\UrlShortener\Repositories\UrlRepository;
use YorCreative\UrlShortener\Tests\TestCase;

class UrlRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     *
     * @group UrlRepository
     */
    public function it_can_find_ownership_record()
    {
        $ownerable = ShortUrlOwnership::factory()->create();

        $record = UrlRepository::findOrCreateOwnershipRecord([
            'short_url_id' => $ownerable->short_url_id,
            'ownerable_type' => $ownerable->ownerable_type,
            'ownerable_id' => $ownerable->ownerable_id,
        ]);

        $this->assertTrue($ownerable->id == $record->id);
    }

    /**
     * @test
     *
     * @group UrlRepository
     */
    public function it_can_create_ownership_record()
    {
        $ownerable = [
            'short_url_id' => 1,
            'ownerable_type' => 'Tests\Models\DemoOwner',
            'ownerable_id' => 1,
        ];

        $this->assertDatabaseMissing('short_url_ownerships', $ownerable);

        UrlRepository::findOrCreateOwnershipRecord($ownerable);

        $this->assertDatabaseHas('short_url_ownerships', $ownerable);
    }

    /**
     * @test
     *
     * @group UrlRepository
     */
    public function it_can_find_short_url_by_hash()
    {
        $shortUrl = UrlRepository::findByHash($this->hashed);
        $this->assertTrue($shortUrl->id == $this->shortUrl->id);
    }

    /**
     * @test
     *
     * @group UrlRepository
     */
    public function it_can_create_a_short_url()
    {
        $plain_text = $this->plain_text.rand(9, 3333);
        $identifier = 'xyz';

        UrlRepository::create([
            'plain_text' => $plain_text,
            'hashed' => md5($plain_text),
            'identifier' => $identifier,
        ]);

        $this->assertDatabaseHas(
            'short_urls',
            [
                'identifier' => 'xyz',
            ]
        );
    }

    /**
     * @test
     *
     * @group UrlRepository
     */
    public function it_can_update_a_short_url()
    {
        $this->assertNull($this->shortUrl->activation);

        UrlRepository::updateShortUrl($this->identifier, [
            'activation' => Carbon::now()->timestamp,
        ]);

        $shortUrl = UrlRepository::findByIdentifier($this->identifier);

        $this->assertNotNull($shortUrl->activation);
    }

    /**
     * @test
     *
     * @group UrlRepository
     */
    public function it_can_bool_that_identifier_exists()
    {
        $this->assertFalse(UrlRepository::identifierExists($this->identifier.'333'));

        $this->assertTrue(UrlRepository::identifierExists($this->identifier));
    }
}

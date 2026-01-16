<?php

namespace YorCreative\UrlShortener\Tests\Unit\Repositories;

use YorCreative\UrlShortener\Models\ShortUrlLocation;
use YorCreative\UrlShortener\Repositories\LocationRepository;
use YorCreative\UrlShortener\Tests\TestCase;

class LocationRepositoryTest extends TestCase
{
    /**
     * @test
     *
     * @group LocationRepository
     */
    public function it_can_find_location_by_ip()
    {
        $location = ShortUrlLocation::create([
            'ip' => '192.168.1.100',
            'countryCode' => 'US',
            'regionCode' => 'CA',
        ]);

        $found = LocationRepository::findIp('192.168.1.100');

        $this->assertNotNull($found);
        $this->assertEquals('192.168.1.100', $found->ip);
    }

    /**
     * @test
     *
     * @group LocationRepository
     */
    public function it_returns_null_for_unknown_ip()
    {
        $found = LocationRepository::findIp('10.0.0.1');

        $this->assertNull($found);
    }

    /**
     * @test
     *
     * @group LocationRepository
     */
    public function it_creates_new_location_record()
    {
        $locationData = [
            'ip' => '203.0.113.50',
            'countryCode' => 'GB',
            'regionCode' => 'LND',
            'city' => 'London',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ];

        $location = LocationRepository::findOrCreateLocationRecord($locationData);

        $this->assertInstanceOf(ShortUrlLocation::class, $location);
        $this->assertEquals('203.0.113.50', $location->ip);
        $this->assertEquals('GB', $location->countryCode);

        $this->assertDatabaseHas('short_url_locations', [
            'ip' => '203.0.113.50',
            'countryCode' => 'GB',
        ]);
    }

    /**
     * @test
     *
     * @group LocationRepository
     */
    public function it_finds_existing_location_record()
    {
        // Create existing location
        $existing = ShortUrlLocation::create([
            'ip' => '198.51.100.1',
            'countryCode' => 'DE',
            'regionCode' => 'BE',
        ]);

        // Try to find or create with same IP and codes
        $found = LocationRepository::findOrCreateLocationRecord([
            'ip' => '198.51.100.1',
            'countryCode' => 'DE',
            'regionCode' => 'BE',
        ]);

        $this->assertEquals($existing->id, $found->id);
    }

    /**
     * @test
     *
     * @group LocationRepository
     */
    public function it_creates_location_with_only_ip()
    {
        $locationData = ['ip' => '172.16.0.1'];

        $location = LocationRepository::findOrCreateLocationRecord($locationData);

        $this->assertInstanceOf(ShortUrlLocation::class, $location);
        $this->assertEquals('172.16.0.1', $location->ip);

        $this->assertDatabaseHas('short_url_locations', [
            'ip' => '172.16.0.1',
        ]);
    }

    /**
     * @test
     *
     * @group LocationRepository
     */
    public function it_returns_unknown_location_array()
    {
        $result = LocationRepository::locationUnknown('10.20.30.40');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('ip', $result);
        $this->assertEquals('10.20.30.40', $result['ip']);
        $this->assertCount(1, $result);
    }

    /**
     * @test
     *
     * @group LocationRepository
     */
    public function it_handles_partial_location_data()
    {
        // Location with country but no region
        $locationData = [
            'ip' => '192.0.2.1',
            'countryCode' => 'FR',
        ];

        $location = LocationRepository::findOrCreateLocationRecord($locationData);

        $this->assertInstanceOf(ShortUrlLocation::class, $location);
        $this->assertEquals('FR', $location->countryCode);
    }

    /**
     * @test
     *
     * @group LocationRepository
     */
    public function it_differentiates_locations_by_region()
    {
        // Create location for same IP but different regions
        $location1 = LocationRepository::findOrCreateLocationRecord([
            'ip' => '203.0.113.100',
            'countryCode' => 'US',
            'regionCode' => 'NY',
        ]);

        $location2 = LocationRepository::findOrCreateLocationRecord([
            'ip' => '203.0.113.100',
            'countryCode' => 'US',
            'regionCode' => 'CA',
        ]);

        // Should create two different records
        $this->assertNotEquals($location1->id, $location2->id);
    }
}

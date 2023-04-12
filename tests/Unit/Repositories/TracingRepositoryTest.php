<?php

namespace YorCreative\UrlShortener\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionException;
use YorCreative\UrlShortener\Models\ShortUrl;
use YorCreative\UrlShortener\Repositories\TracingRepository;
use YorCreative\UrlShortener\Tests\TestCase;

class TracingRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     *
     * @group TracingRepository
     *
     * @throws ReflectionException
     */
    public function it_can_detect_all_utm_parameters()
    {
        foreach ($this->getAllowedParameters() as $parameter) {
            $this->assertTrue(
                TracingRepository::hasTracing(
                    $this->buildRequestForTest([
                        $parameter => 'something',
                    ])
                )
            );
        }
    }

    /**
     * @return mixed
     *
     * @throws ReflectionException
     */
    private function getAllowedParameters()
    {
        $class = new ReflectionClass(TracingRepository::class);
        $method = $class->getMethod('allowedParameters');
        $method->setAccessible(true);

        return $method->invoke((object) TracingRepository::class, []);
    }

    private function buildRequestForTest(array $query): Request
    {
        return new Request($query);
    }

    /**
     * @test
     *
     * @group TracingRepository
     *
     * @throws ReflectionException
     */
    public function it_has_correct_allowed_parameters()
    {
        foreach ($this->getAllowedParameters() as $parameter) {
            $this->assertTrue(
                in_array($parameter, [
                    TracingRepository::$ID,
                    TracingRepository::$SOURCE,
                    TracingRepository::$MEDIUM,
                    TracingRepository::$CAMPAIGN,
                    TracingRepository::$CONTENT,
                    TracingRepository::$TERM,
                ])
            );
        }
    }

    /**
     * @test
     *
     * @group TracingRepository
     */
    public function it_can_create_a_trace_record()
    {
        $utm_query = [
            'short_url_id' => ShortUrl::factory()->create()->id,
            TracingRepository::$ID => '1234',
            TracingRepository::$CAMPAIGN => 'buffer',
            TracingRepository::$SOURCE => 'linkedin',
            TracingRepository::$CAMPAIGN => 'sponsored_ad',
            TracingRepository::$MEDIUM => 'social',
            TracingRepository::$TERM => 'marketing+software',
            TracingRepository::$CONTENT => 'xyz',
        ];

        TracingRepository::create($utm_query);

        $this->assertDatabaseHas('short_url_tracings', $utm_query);
    }
}

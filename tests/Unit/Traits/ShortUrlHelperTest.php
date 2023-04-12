<?php

use YorCreative\UrlShortener\Exceptions\UrlServiceException;
use YorCreative\UrlShortener\Tests\Models\DemoOwner;
use YorCreative\UrlShortener\Tests\TestCase;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

class ShortUrlHelperTest extends TestCase
{
    use ShortUrlHelper;

    /**
     * @test
     *
     * @group Traits
     */
    public function it_can_validate_ownership_is_array_of_models_filter()
    {
        $model = DemoOwner::factory()->create();

        $filter = [
            'ownership' => [
                $model,
            ],
        ];

        $this->assertEquals($filter, $this->filterClickValidation($filter));
    }

    /**
     * @test
     *
     * @group Traits
     */
    public function it_can_validate_ownership_is_not_array_of_models_filter()
    {
        $model = DemoOwner::factory()->create();

        $filter = [
            'ownership' => [
                $model->id,
            ],
        ];

        try {
            $this->filterClickValidation($filter);
        } catch (UrlServiceException $exception) {
            $this->assertEquals(
                '{"ownership.0":["Ownership must be an instance of the owners model."]}',
                $exception->getMessage()
            );
        }
    }

    /**
     * @test
     *
     * @group Traits
     */
    public function it_can_build_short_url()
    {
        $this->assertEquals(
            "localhost.test/v1/$this->identifier",
            $this->builtShortUrl($this->identifier)
        );
    }
}

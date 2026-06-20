<?php

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use YorCreative\UrlShortener\Exceptions\UrlServiceException;
use YorCreative\UrlShortener\Tests\Models\DemoOwner;
use YorCreative\UrlShortener\Tests\TestCase;
use YorCreative\UrlShortener\Traits\ShortUrlHelper;

class ShortUrlHelperTest extends TestCase
{
    use ShortUrlHelper;

    #[Test]
    #[Group('Traits')]
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

    #[Test]
    #[Group('Traits')]
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

    #[Test]
    #[Group('Traits')]
    public function it_can_validate_failure_activation_outcome_filter()
    {
        $filter = [
            'outcome' => [
                6,
            ],
        ];

        $this->assertEquals($filter, $this->filterClickValidation($filter));
    }

    #[Test]
    #[Group('Traits')]
    public function it_can_validate_status_array_filter()
    {
        $filter = [
            'status' => [
                'active',
                'expired',
                'expiring',
            ],
        ];

        $this->assertEquals($filter, $this->filterClickValidation($filter));
    }

    #[Test]
    #[Group('Traits')]
    public function it_rejects_scalar_status_filter()
    {
        $this->expectException(UrlServiceException::class);
        $this->expectExceptionMessage('status');

        $this->filterClickValidation([
            'status' => 'active',
        ]);
    }

    #[Test]
    #[Group('Traits')]
    public function it_can_build_short_url()
    {
        $host = config('urlshortener.branding.host') ?? 'localhost.test';
        $prefix = config('urlshortener.branding.prefix') ?? 'v1';
        $expected = rtrim($host, '/').'/'.trim($prefix, '/').'/'.$this->identifier;

        $this->assertEquals(
            $expected,
            $this->builtShortUrl($this->identifier)
        );
    }
}

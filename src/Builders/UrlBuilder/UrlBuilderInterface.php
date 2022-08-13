<?php

namespace YorCreative\UrlShortener\Builders\UrlBuilder;

interface UrlBuilderInterface
{
    public function build(): string;

    public function withPassword(string $password): UrlBuilder;
}

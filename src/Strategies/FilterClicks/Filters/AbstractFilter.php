<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

abstract class AbstractFilter
{
    public function hasOptions(array $options): bool
    {
        return
            count(
                array_intersect(
                    $options,
                    $this->getAvailableFilterOptions()
                )
            ) > 0;
    }

    abstract public function getAvailableFilterOptions(): array;

    public function getOptions(array $options): array
    {
        return array_intersect(
            $options,
            $this->getAvailableFilterOptions()
        );
    }

    abstract public function canProcess(array $filter): bool;
}

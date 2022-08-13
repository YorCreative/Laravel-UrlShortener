<?php

namespace YorCreative\UrlShortener\Strategies\FilterClicks\Filters;

abstract class AbstractFilter
{
    /**
     * @param  array  $options
     * @return bool
     */
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

    /**
     * @return array
     */
    abstract public function getAvailableFilterOptions(): array;

    /**
     * @param  array  $options
     * @return array
     */
    public function getOptions(array $options): array
    {
        return array_intersect(
            $options,
            $this->getAvailableFilterOptions()
        );
    }

    /**
     * @param  array  $filter
     * @return bool
     */
    abstract public function canProcess(array $filter): bool;
}

<?php

namespace IDS\Filter\Provider;

interface FilterProviderInterface
{
    /**
     * @return \IDS\Filter[]
     */
    public function getFilters(): array;
}

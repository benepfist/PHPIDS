<?php

namespace IDS\Filter\Provider;

use IDS\Caching\CacheInterface;

class CachedFilterProvider implements FilterProviderInterface
{
    private FilterProviderInterface $innerProvider;
    private CacheInterface $cache;

    public function __construct(FilterProviderInterface $innerProvider, CacheInterface $cache)
    {
        $this->innerProvider = $innerProvider;
        $this->cache = $cache;
    }

    public function getFilters(): array
    {
        $cached = $this->cache->getCache();

        if (is_array($cached) && !empty($cached)) {
            $filterSet = [];
            foreach ($cached as $item) {
                $filterSet[] = new \IDS\Filter(
                    $item['id'],
                    $item['rule'],
                    $item['description'],
                    (array) $item['tags'][0],
                    (int) $item['impact']
                );
            }
            return $filterSet;
        }

        // Cache miss -> Load from inner provider
        $filters = $this->innerProvider->getFilters();

        // Convert the object array back to simple array for caching (to match original behaviour)
        $dataToCache = [];
        foreach ($filters as $filter) {
            $dataToCache[] = [
                'id'          => $filter->getId(),
                'rule'        => $filter->getRule(),
                'impact'      => $filter->getImpact(),
                'tags'        => [$filter->getTags()], // The original code nested tags array this way
                'description' => $filter->getDescription()
            ];
        }

        $this->cache->setCache($dataToCache);

        return $filters;
    }
}
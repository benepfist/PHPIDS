<?php

namespace IDS\Filter;

use IDS\Init;
use IDS\Caching\CacheFactory;
use IDS\Filter\Provider\XmlFilterProvider;
use IDS\Filter\Provider\JsonFilterProvider;
use IDS\Filter\Provider\CachedFilterProvider;

class Storage
{
    protected string $source = '';
    protected ?array $cacheSettings = null;
    protected ?\IDS\Caching\CacheInterface $cache = null;
    protected array $filterSet = [];

    /**
     * Constructor
     *
     * Loads filters based on provided IDS_Init settings.
     *
     * @param \IDS\Init $init IDS_Init instance
     *
     * @throws \InvalidArgumentException if unsupported filter type is given
     * @return void
     */
    final public function __construct(Init $init)
    {
        if ($init->config) {
            $caching = isset($init->config['Caching']['caching']) ? $init->config['Caching']['caching'] : 'none';
            $type    = $init->config['General']['filter_type'];
            $this->source = $init->getBasePath() . $init->config['General']['filter_path'];

            $provider = null;
            switch ($type) {
                case 'xml':
                    $provider = new XmlFilterProvider($this->source);
                    break;
                case 'json':
                    $provider = new JsonFilterProvider($this->source);
                    break;
                default:
                    throw new \InvalidArgumentException('Unsupported filter type.');
            }

            if ($caching && $caching !== 'none') {
                $this->cacheSettings = $init->config['Caching'];
                $cache = CacheFactory::factory($init, 'storage');
                if ($cache instanceof \IDS\Caching\CacheInterface) {
                    $this->cache = $cache;
                    $provider = new CachedFilterProvider($provider, $this->cache);
                }
            }

            $this->setFilterSet($provider->getFilters());
        }
    }

    /**
     * Sets the filter array
     *
     * @param \IDS\Filter[] $filterSet array containing multiple IDS_Filter instances
     *
     * @return self
     */
    final public function setFilterSet(array $filterSet): self
    {
        foreach ($filterSet as $filter) {
            $this->addFilter($filter);
        }

        return $this;
    }

    /**
     * Returns registered filters
     *
     * @return \IDS\Filter[]
     */
    final public function getFilterSet(): array
    {
        return $this->filterSet;
    }

    /**
     * Adds a filter
     *
     * @param \IDS\Filter $filter IDS_Filter instance
     *
     * @return self
     */
    final public function addFilter(\IDS\Filter $filter): self
    {
        $this->filterSet[] = $filter;

        return $this;
    }
}

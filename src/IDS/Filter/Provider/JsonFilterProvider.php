<?php

namespace IDS\Filter\Provider;

class JsonFilterProvider implements FilterProviderInterface
{
    private string $source;

    public function __construct(string $source)
    {
        $this->source = $source;
    }

    public function getFilters(): array
    {
        if (!extension_loaded('Json')) {
            throw new \RuntimeException('json extension is not loaded.');
        }

        if (!file_exists($this->source)) {
            throw new \InvalidArgumentException(sprintf("Invalid config: %s doesn't exist.", $this->source));
        }

        $json = file_get_contents($this->source);
        if ($json === false) {
            throw new \RuntimeException('JSON file could not be read.');
        }

        $filters = json_decode($json);

        if (!$filters || !isset($filters->filters->filter)) {
            throw new \RuntimeException('JSON data could not be loaded. Make sure you specified the correct path.');
        }

        $filterSet = [];
        foreach ($filters->filters->filter as $filterNode) {
            $tags = array_values((array) $filterNode->tags);
            $filterSet[] = new \IDS\Filter(
                (int) $filterNode->id,
                (string) $filterNode->rule,
                (string) $filterNode->description,
                (array) $tags[0],
                (int) $filterNode->impact
            );
        }

        return $filterSet;
    }
}
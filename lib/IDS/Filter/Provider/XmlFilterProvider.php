<?php

namespace IDS\Filter\Provider;

class XmlFilterProvider implements FilterProviderInterface
{
    private string $source;

    public function __construct(string $source)
    {
        $this->source = $source;
    }

    public function getFilters(): array
    {
        if (!extension_loaded('SimpleXML')) {
            throw new \RuntimeException('SimpleXML is not loaded.');
        }

        if (!file_exists($this->source)) {
            throw new \InvalidArgumentException(sprintf("Invalid config: %s doesn't exist.", $this->source));
        }

        $previous = libxml_use_internal_errors(true);
        try {
            if (LIBXML_VERSION >= 20621) {
                $filters = simplexml_load_file($this->source, null, LIBXML_COMPACT);
            } else {
                $filters = simplexml_load_file($this->source);
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (empty($filters)) {
            throw new \RuntimeException('XML data could not be loaded. Make sure you specified the correct path.');
        }

        $filterSet = [];
        foreach ($filters->filter as $filterNode) {
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

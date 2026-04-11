<?php

namespace IDS;

class ConverterPipeline
{
    /** @var list<ConverterInterface> */
    private array $converters = [];

    public function add(ConverterInterface $converter): self
    {
        $this->converters[] = $converter;
        return $this;
    }

    public function runAll(mixed $value): string
    {
        $value = (string) $value;
        foreach ($this->converters as $converter) {
            $value = $converter->convert($value);
        }
        return $value;
    }
}

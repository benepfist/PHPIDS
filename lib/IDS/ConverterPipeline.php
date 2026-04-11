<?php

namespace IDS;

class ConverterPipeline
{
    private array $converters = [];

    public function add(ConverterInterface $converter): self
    {
        $this->converters[] = $converter;
        return $this;
    }

    public function runAll(string $value): string
    {
        foreach ($this->converters as $converter) {
            $value = $converter->convert($value);
        }
        return $value;
    }
}

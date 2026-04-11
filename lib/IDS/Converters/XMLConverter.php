<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class XMLConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        $converted = strip_tags($value);
        if (!$converted || $converted === $value) {
            return $value;
        } else {
            return $value . "\n" . $converted;
        }
    }
}

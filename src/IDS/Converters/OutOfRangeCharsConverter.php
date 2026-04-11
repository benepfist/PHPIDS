<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class OutOfRangeCharsConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        $values = str_split($value);
        foreach ($values as $item) {
            if (ord($item) >= 127) {
                $value = str_replace($item, ' ', $value);
            }
        }

        return $value;
    }
}

<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class NestedBase64Converter implements ConverterInterface
{
    public function convert(string $value): string
    {
        $matches = array();
        preg_match_all('/(?:^|[,&?])\s*([a-z0-9]{50,}=*)(?:\W|$)/im', $value, $matches);

        foreach ($matches[1] as $item) {
            if (!preg_match('/[a-f0-9]{32}/i', $item)) {
                $base64_item = base64_decode($item);
                $value = str_replace($item, $base64_item, $value);
            }
        }

        return $value;
    }
}

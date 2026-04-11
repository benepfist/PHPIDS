<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class SQLHexConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        $matches = [];
        if (preg_match_all('/(?:(?:\A|[^\d])0x[a-f\d]{3,}[a-f\d]*)+/im', $value, $matches)) {
            foreach ($matches[0] as $match) {
                $converted = '';
                foreach (str_split($match, 2) as $hex_index) {
                    if (preg_match('/[a-f\d]{2,3}/i', $hex_index)) {
                        $converted .= chr((int) hexdec($hex_index));
                    }
                }
                $value = str_replace($match, $converted, $value);
            }
        }
        // take care of hex encoded ctrl chars
        $value = preg_replace('/0x\d+/m', ' 1 ', $value) ?? $value;

        return $value;
    }
}

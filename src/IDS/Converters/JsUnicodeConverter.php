<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class JsUnicodeConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        $matches = [];
        preg_match_all('/\\\u[0-9a-f]{4}/ims', $value, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                $chr = chr((int) hexdec(substr($match, 2, 4)));
                $value = str_replace($match, $chr, $value);
            }
            $value .= "\n\u0001";
        }

        return $value;
    }
}

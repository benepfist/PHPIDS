<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class SQLKeywordsConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        $pattern = array(
            '/(?:is\s+null)|(like\s+null)|' .
            '(?:(?:^|\W)in[+\s]*\([\s\d"]+[^()]*\))/ims'
        );
        $value   = preg_replace($pattern, '"=0', $value) ?? $value;

        $value   = preg_replace('/[^\w\)]+\s*like\s*[^\w\s]+/ims', '1" OR "1"', $value) ?? $value;
        $value   = preg_replace('/null([,"\s])/ims', '0$1', $value) ?? $value;
        $value   = preg_replace('/\d+\./ims', ' 1', $value) ?? $value;
        $value   = preg_replace('/,null/ims', ',0', $value) ?? $value;
        $value   = preg_replace('/(?:between)/ims', 'or', $value) ?? $value;
        $value   = preg_replace('/(?:and\s+\d+\.?\d*)/ims', '', $value) ?? $value;
        $value   = preg_replace('/(?:\s+and\s+)/ims', ' or ', $value) ?? $value;

        $pattern = array(
            '/(?:not\s+between)|(?:is\s+not)|(?:not\s+in)|' .
            '(?:xor|<>|rlike(?:\s+binary)?)|' .
            '(?:regexp\s+binary)|' .
            '(?:sounds\s+like)/ims'
        );
        $value   = preg_replace($pattern, '!', $value) ?? $value;
        $value   = preg_replace('/"\s+\d/', '"', $value) ?? $value;
        $value   = preg_replace('/(\W)div(\W)/ims', '$1 OR $2', $value) ?? $value;
        $value   = preg_replace('/\/(?:\d+|null)/', '', $value) ?? $value;

        return $value;
    }
}

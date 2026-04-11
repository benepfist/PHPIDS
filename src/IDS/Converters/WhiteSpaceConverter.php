<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class WhiteSpaceConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        //check for inline linebreaks
        $search = ['\r', '\n', '\f', '\t', '\v'];
        $value  = str_replace($search, ';', $value);

        // replace replacement characters regular spaces
        $value = str_replace('�', ' ', $value);

        //convert real linebreaks
        return (string) preg_replace('/(?:\n|\r|\v)/m', '  ', $value);
    }
}

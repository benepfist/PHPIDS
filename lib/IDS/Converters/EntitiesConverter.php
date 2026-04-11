<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class EntitiesConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        $converted = null;

        //deal with double encoded payload
        $value = preg_replace('/&amp;/', '&', $value);

        if (preg_match('/&#x?[\w]+/ms', $value)) {
            $converted = preg_replace('/(&#x?[\w]{2}\d?);?/ms', '$1;', $value);
            $converted = html_entity_decode($converted, ENT_QUOTES, 'UTF-8');
            $value    .= "\n" . str_replace(';;', ';', $converted);
        }

        // normalize obfuscated protocol handlers
        $value = preg_replace(
            '/(?:j\s*a\s*v\s*a\s*s\s*c\s*r\s*i\s*p\s*t\s*:)|(d\s*a\s*t\s*a\s*:)/ms',
            'javascript:',
            $value
        );

        return $value;
    }
}

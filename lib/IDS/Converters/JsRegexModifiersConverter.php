<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class JsRegexModifiersConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        return preg_replace('/\/[gim]+/', '/', $value);
    }
}

<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class QuotesConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        // normalize different quotes to "
        $pattern = ['\'', '`', '´', '’', '‘'];
        $value   = str_replace($pattern, '"', $value);

        //make sure harmless quoted strings don't generate false alerts
        $value = preg_replace('/^"([^"=\\!><~]+)"$/', '$1', $value) ?? $value;

        return $value;
    }
}

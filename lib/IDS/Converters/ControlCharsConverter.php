<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class ControlCharsConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        // critical ctrl values
        $search = array(
            chr(0), chr(1), chr(2), chr(3), chr(4), chr(5),
            chr(6), chr(7), chr(8), chr(11), chr(12), chr(14),
            chr(15), chr(16), chr(17), chr(18), chr(19), chr(24),
            chr(25), chr(192), chr(193), chr(238), chr(255), '\\0'
        );

        $value = str_replace($search, '%00', $value);

        //take care for malicious unicode characters
        $encoded = preg_replace(
            '/(?:%E(?:2|3)%8(?:0|1)%(?:A|8|9)\w|%EF%BB%BF|%EF%BF%BD)|(?:&#(?:65|8)\d{3};?)/i',
            '',
            urlencode($value)
        ) ?? urlencode($value);
        $value = urldecode($encoded);
        $value = urlencode($value);
        $value = preg_replace('/(?:%F0%80%BE)/i', '>', $value) ?? $value;
        $value = preg_replace('/(?:%F0%80%BC)/i', '<', $value) ?? $value;
        $value = preg_replace('/(?:%F0%80%A2)/i', '"', $value) ?? $value;
        $value = preg_replace('/(?:%F0%80%A7)/i', '\'', $value) ?? $value;
        $value = urldecode($value);

        $value = preg_replace('/(?:%ff1c)/', '<', $value) ?? $value;
        $value = preg_replace('/(?:&[#x]*(200|820|200|820|zwn?j|lrm|rlm)\w?;?)/i', '', $value) ?? $value;
        $value = preg_replace(
            '/(?:&#(?:65|8)\d{3};?)|' .
            '(?:&#(?:56|7)3\d{2};?)|' .
            '(?:&#x(?:fe|20)\w{2};?)|' .
            '(?:&#x(?:d[c-f])\w{2};?)/i',
            '',
            $value
        ) ?? $value;

        $value = str_replace(
            array(
                '«',
                '〈',
                '＜',
                '‹',
                '〈',
                '⟨'
            ),
            '<',
            $value
        );
        $value = str_replace(
            array(
                '»',
                '〉',
                '＞',
                '›',
                '〉',
                '⟩'
            ),
            '>',
            $value
        );

        return $value;
    }
}

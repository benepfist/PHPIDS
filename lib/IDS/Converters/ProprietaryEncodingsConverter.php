<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class ProprietaryEncodingsConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        //Xajax error reportings
        $value = preg_replace('/<!\[CDATA\[(\W+)\]\]>/im', '$1', $value);

        //strip false alert triggering apostrophes
        $value = preg_replace('/(\w)\"(s)/m', '$1$2', $value);

        //strip quotes within typical search patterns
        $value = preg_replace('/^"([^"=\\!><~]+)"$/', '$1', $value);

        //OpenID login tokens
        $value = preg_replace('/{[\w-]{8,9}\}(?:\{[\w=]{8}\}){2}/', '', $value);

        //convert Content and \sdo\s to null
        $value = preg_replace('/Content|\Wdo\s/', '', $value);

        //strip emoticons
        $value = preg_replace(
            '/(?:\s[:;]-[)\/PD]+)|(?:\s;[)PD]+)|(?:\s:[)PD]+)|-\.-|\^\^/m',
            '',
            $value
        );

        //normalize separation char repetion
        $value = preg_replace('/([.+~=*_\-;])\1{2,}/m', '$1', $value);

        //normalize multiple single quotes
        $value = preg_replace('/"{2,}/m', '"', $value);

        //normalize quoted numerical values and asterisks
        $value = preg_replace('/"(\d+)"/m', '$1', $value);

        //normalize pipe separated request parameters
        $value = preg_replace('/\|(\w+=\w+)/m', '&$1', $value);

        //normalize ampersand listings
        $value = preg_replace('/(\w\s)&\s(\w)/', '$1$2', $value);

        //normalize escaped RegExp modifiers
        $value = preg_replace('/\/\\\(\w)/', '/$1', $value);

        return $value;
    }
}

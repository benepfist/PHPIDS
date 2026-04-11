<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class CommentedConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        // check for existing comments
        if (preg_match('/(?:\<!-|-->|\/\*|\*\/|\/\/\W*\w+\s*$)|(?:--[^-]*-)/ms', $value)) {

            $pattern = array(
                '/(?:(?:<!)(?:(?:--(?:[^-]*(?:-[^-]+)*)--\s*)*)(?:>))/ms',
                '/(?:(?:\/\*\/*[^\/\*]*)+\*\/)/ms',
                '/(?:--[^-]*-)/ms'
            );

            $converted = (string) preg_replace($pattern, ';', $value);
            $value    .= "\n" . $converted;
        }

        //make sure inline comments are detected and converted correctly
        $value = (string) preg_replace('/(<\w+)\/+(\w+=?)/m', '$1/$2', $value);
        $value = (string) preg_replace('/[^\\\:]\/\/(.*)$/m', '/**/$1', $value);
        $value = (string) preg_replace('/([^\-&])#.*[\r\n\v\f]/m', '$1', $value);
        $value = (string) preg_replace('/([^&\-])#.*\n/m', '$1 ', $value);
        $value = (string) preg_replace('/^#.*\n/m', ' ', $value);

        return $value;
    }
}

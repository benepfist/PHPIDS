<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class ConcatenatedConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        //normalize remaining backslashes
        $normalized = preg_replace('/(\w)\\\/', "$1", $value) ?? $value;
        if ($value != $normalized) {
            $value .= $normalized;
        }

        $compare = stripslashes($value);

        $pattern = ['/(?:<\/\w+>\+<\w+>)/s', '/(?:":\d+[^"[]+")/s', '/(?:"?"\+\w+\+")/s', '/(?:"\s*;[^"]+")|(?:";[^"]+:\s*")/s', '/(?:"\s*(?:;|\+).{8,18}:\s*")/s', '/(?:";\w+=)|(?:!""&&")|(?:~)/s', '/(?:"?"\+""?\+?"?)|(?:;\w+=")|(?:"[|&]{2,})/s', '/(?:"\s*\W+")/s', '/(?:";\w\s*\+=\s*\w?\s*")/s', '/(?:"[|&;]+\s*[^|&\n]*[|&]+\s*"?)/s', '/(?:";\s*\w+\W+\w*\s*[|&]*")/s', '/(?:"\s*"\s*\.)/s', '/(?:\s*new\s+\w+\s*[+",])/', '/(?:(?:^|\s+)(?:do|else)\s+)/', '/(?:[{(]\s*new\s+\w+\s*[)}])/', '/(?:(this|self)\.)/', '/(?:undefined)/', '/(?:in\s+)/'];

        // strip out concatenations
        $converted = preg_replace($pattern, '', $compare) ?? $compare;

        //strip object traversal
        $converted = preg_replace('/\w(\.\w\()/', "$1", $converted) ?? $converted;

        // normalize obfuscated method calls
        $converted = preg_replace('/\)\s*\+/', ")", $converted) ?? $converted;

        //convert JS special numbers
        $converted = preg_replace(
            '/(?:\(*[.\d]e[+-]*[^a-z\W]+\)*)|(?:NaN|Infinity)\W/ims',
            '1',
            $converted
        ) ?? $converted;

        if ($converted && ($compare != $converted)) {
            $value .= "\n" . $converted;
        }

        return $value;
    }
}

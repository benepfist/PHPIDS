<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class JsCharcodeConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        $matches = [];

        // check if value matches typical charCode pattern
        if (preg_match_all('/(?:[\d+-=\/\* ]+(?:\s?,\s?[\d+-=\/\* ]+)){4,}/ms', $value, $matches)) {
            $converted = '';
            $string    = implode(',', $matches[0]);
            $string    = preg_replace('/\s/', '', $string) ?? $string;
            $string    = preg_replace('/\w+=/', '', $string) ?? $string;
            $charcode  = explode(',', $string);

            foreach ($charcode as $char) {
                $char = preg_replace('/\W0/s', '', $char) ?? $char;

                if (preg_match_all('/\d*[+-\/\* ]\d+/', $char, $matches)) {
                    $expr = implode('', $matches[0]);
                    $tokens = preg_split('/([+\-\/*])/', $expr, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                    $tokens = $tokens === false ? [] : $tokens;

                    $result = 0.0;
                    if (count($tokens) > 0) {
                        $result = (float) array_shift($tokens);
                        while (count($tokens) >= 2) {
                            $op = array_shift($tokens);
                            $val = (float) array_shift($tokens);

                            switch ($op) {
                                case '+': $result += $val; break;
                                case '-': $result -= $val; break;
                                case '*': $result *= $val; break;
                                case '/': $result = $val != 0 ? $result / $val : 0; break;
                            }
                        }
                    }

                    if ($result >= 20 && $result <= 127) {
                        $converted .= chr((int) $result);
                    }

                } elseif (!empty($char) && $char >= 20 && $char <= 127) {
                    $converted .= chr((int) $char);
                }
            }

            $value .= "\n" . $converted;
        }

        // check for octal charcode pattern
        if (preg_match_all('/(?:(?:[\\\]+\d+[ \t]*){8,})/ims', $value, $matches)) {
            $converted = '';
            $charcode  = explode('\\', preg_replace('/\s/', '', implode(',', $matches[0])) ?? implode(',', $matches[0]));

            foreach (array_map('octdec', array_filter($charcode)) as $char) {
                if (20 <= $char && $char <= 127) {
                    $converted .= chr((int) $char);
                }
            }
            $value .= "\n" . $converted;
        }

        // check for hexadecimal charcode pattern
        if (preg_match_all('/(?:(?:[\\\]+\w+\s*){8,})/ims', $value, $matches)) {
            $converted = '';
            $charcode  = explode('\\', preg_replace('/[ux]/', '', implode(',', $matches[0])) ?? implode(',', $matches[0]));

            foreach (array_map('hexdec', array_filter($charcode)) as $char) {
                if (20 <= $char && $char <= 127) {
                    $converted .= chr((int) $char);
                }
            }
            $value .= "\n" . $converted;
        }

        return $value;
    }
}

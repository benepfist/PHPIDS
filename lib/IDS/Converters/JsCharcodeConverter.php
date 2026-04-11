<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class JsCharcodeConverter implements ConverterInterface
{
    public function convert(string $value): string
    {
        $matches = array();

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
                    $match = preg_split('/(\W?\d+)/', implode('', $matches[0]), -1, PREG_SPLIT_DELIM_CAPTURE);
                    $match = $match === false ? [] : $match;

                    $sum = array_sum($match);
                    if ($sum >= 20 && $sum <= 127) {
                        $converted .= chr((int) $sum);
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

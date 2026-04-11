<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class UTF7Converter implements ConverterInterface
{
    public function convert(string $value): string
    {
        if (preg_match('/\+A\w+-?/m', $value)) {
            if (function_exists('mb_convert_encoding')) {
                if (version_compare(PHP_VERSION, '8.4.0', '<')) {
                    $tmp_chars = str_split($value);
                    $value = '';
                    foreach ($tmp_chars as $char) {
                        if (ord($char) <= 127) {
                            $value .= $char;
                        }
                    }
                }
                $value .= "\n" . mb_convert_encoding($value, 'UTF-8', 'UTF-7');
            } else {
                //list of all critical UTF7 codepoints
                $schemes = ['+ACI-'      => '"', '+ADw-'      => '<', '+AD4-'      => '>', '+AFs-'      => '[', '+AF0-'      => ']', '+AHs-'      => '{', '+AH0-'      => '}', '+AFw-'      => '\\', '+ADs-'      => ';', '+ACM-'      => '#', '+ACY-'      => '&', '+ACU-'      => '%', '+ACQ-'      => '$', '+AD0-'      => '=', '+AGA-'      => '`', '+ALQ-'      => '"', '+IBg-'      => '"', '+IBk-'      => '"', '+AHw-'      => '|', '+ACo-'      => '*', '+AF4-'      => '^', '+ACIAPg-'   => '">', '+ACIAPgA8-' => '">'];

                $value = str_ireplace(
                    array_keys($schemes),
                    array_values($schemes),
                    $value
                );
            }
        }

        return $value;
    }
}

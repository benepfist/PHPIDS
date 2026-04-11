<?php

namespace IDS;

class Converter
{
    public static ?ConverterPipeline $pipeline = null;

    public static function runAll(mixed $value): string
    {
        if (!self::$pipeline) {
            self::$pipeline = new ConverterPipeline();
            self::$pipeline->add(new Converters\CommentedConverter());
            self::$pipeline->add(new Converters\WhiteSpaceConverter());
            self::$pipeline->add(new Converters\JsCharcodeConverter());
            self::$pipeline->add(new Converters\JsRegexModifiersConverter());
            self::$pipeline->add(new Converters\EntitiesConverter());
            self::$pipeline->add(new Converters\QuotesConverter());
            self::$pipeline->add(new Converters\SQLHexConverter());
            self::$pipeline->add(new Converters\SQLKeywordsConverter());
            self::$pipeline->add(new Converters\ControlCharsConverter());
            self::$pipeline->add(new Converters\NestedBase64Converter());
            self::$pipeline->add(new Converters\OutOfRangeCharsConverter());
            self::$pipeline->add(new Converters\XMLConverter());
            self::$pipeline->add(new Converters\JsUnicodeConverter());
            self::$pipeline->add(new Converters\UTF7Converter());
            self::$pipeline->add(new Converters\ConcatenatedConverter());
            self::$pipeline->add(new Converters\ProprietaryEncodingsConverter());
            self::$pipeline->add(new Converters\UrlencodeSqlCommentConverter());
        }
        return self::$pipeline->runAll($value);
    }

    public static function runCentrifuge(mixed $value, ?Monitor $monitor = null): string
    {
        $value = (string) $value;

        $threshold = 3.49;
        if (strlen($value) > 25) {
            //strip padding
            $tmp_value = preg_replace('/\s{4}|==$/m', '', $value);
            $tmp_value = preg_replace(
                '/\s{4}|[\p{L}\d\+\-=,.%()]{8,}/m',
                'aaa',
                $tmp_value
            );

            // Check for the attack char ratio
            $tmp_value = preg_replace('/([*.!?+-])\1{1,}/m', '$1', $tmp_value);
            $tmp_value = preg_replace('/"[\p{L}\d\s]+"/m', '', $tmp_value);

            $stripped_length = strlen(
                preg_replace(
                    '/[\d\s\p{L}\.:,%&\/><\-)!|]+/m',
                    '',
                    $tmp_value
                )
            );
            $overall_length = strlen(
                preg_replace(
                    '/([\d\s\p{L}:,\.]{3,})+/m',
                    'aaa',
                    preg_replace('/\s{2,}/m', '', $tmp_value)
                )
            );

            if ($stripped_length != 0 && $overall_length/$stripped_length <= $threshold) {
                if ($monitor !== null) {
                    $monitor->centrifuge['ratio']     = $overall_length / $stripped_length;
                    $monitor->centrifuge['threshold'] = $threshold;
                }

                $value .= "\n$[!!!]";
            }
        }

        if (strlen($value) > 40) {
            // Replace all non-special chars
            $converted =  preg_replace('/[\w\s\p{L},.:!]/', '', $value);

            // Split string into an array, unify and sort
            $array = str_split($converted);
            $array = array_unique($array);
            asort($array);

            // Normalize certain tokens
            $schemes = array(
                '~' => '+',
                '^' => '+',
                '|' => '+',
                '*' => '+',
                '%' => '+',
                '&' => '+',
                '/' => '+'
            );

            $converted = implode($array);

            $_keys = array_keys($schemes);
            $_values = array_values($schemes);

            $converted = str_replace($_keys, $_values, $converted);

            $converted = preg_replace('/[+-]\s*\d+/', '+', $converted);
            $converted = preg_replace('/[()[\]{}]/', '(', $converted);
            $converted = preg_replace('/[!?:=]/', ':', $converted);
            $converted = preg_replace('/[^:(+]/', '', stripslashes($converted));

            // Sort again and implode
            $array = str_split($converted);
            asort($array);
            $converted = implode($array);

            if (preg_match('/(?:\({2,}\+{2,}:{2,})|(?:\({2,}\+{2,}:+)|(?:\({3,}\++:{2,})/', $converted)) {
                if ($monitor !== null) {
                    $monitor->centrifuge['converted'] = $converted;
                }

                return $value . "\n" . $converted;
            }
        }

        return $value;
    }
}

<?php

/**
 * PHPIDS
 *
 * Requirements: PHP5, SimpleXML
 *
 * Copyright (c) 2008 PHPIDS group (https://phpids.org)
 *
 * PHPIDS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, version 3 of the License, or
 * (at your option) any later version.
 *
 * PHPIDS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PHPIDS. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.1.6+
 *
 * @category Security
 * @package  PHPIDS
 * @author   Mario Heiderich <mario.heiderich@gmail.com>
 * @author   Christian Matthies <ch0012@gmail.com>
 * @author   Lars Strojny <lars@strojny.net>
 * @license  http://www.gnu.org/licenses/lgpl.html LGPL
 * @link     http://php-ids.org/
 */

/**
 * PHPIDS specific utility class to convert charsets manually
 *
 * Note that if you make use of IDS_Converter::runAll(), existing class
 * methods will be executed in the same order as they are implemented in the
 * class tree!
 *
 * @category  Security
 * @package   PHPIDS
 * @author    Christian Matthies <ch0012@gmail.com>
 * @author    Mario Heiderich <mario.heiderich@gmail.com>
 * @author    Lars Strojny <lars@strojny.net>
 * @copyright 2007-2009 The PHPIDS Group
 * @license   http://www.gnu.org/licenses/lgpl.html LGPL
 * @link      http://php-ids.org/
 */

namespace IDS;

class Converter
{
    /**
     * Runs all converter functions
     *
     * Note that if you make use of IDS_Converter::runAll(), existing class
     * methods will be executed in the same order as they are implemented in the
     * class tree!
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function runAll($value)
    {
        foreach (get_class_methods(__CLASS__) as $method) {
            if (strpos($method, 'run') !== 0) {
                $value = self::$method($value);
            }
        }

        return $value;
    }

    /**
     * Check for comments and erases them if available
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromCommented($value)
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

    /**
     * Strip newlines
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromWhiteSpace($value)
    {
        //check for inline linebreaks
        $search = array('\r', '\n', '\f', '\t', '\v');
        $value  = str_replace($search, ';', $value);

        // replace replacement characters regular spaces
        $value = str_replace('�', ' ', $value);

        //convert real linebreaks
        return (string) preg_replace('/(?:\n|\r|\v)/m', '  ', $value);
    }

    /**
     * Checks for common charcode pattern and decodes them
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromJSCharcode($value)
    {
        $matches = array();

        // check if value matches typical charCode pattern
        if (preg_match_all('/(?:[\d+-=\/\* ]+(?:\s?,\s?[\d+-=\/\* ]+)){4,}/ms', $value, $matches)) {
            $converted = '';
            $string    = implode(',', $matches[0]);
            $string    = preg_replace('/\s/', '', $string);
            $string    = preg_replace('/\w+=/', '', $string);
            $charcode  = explode(',', $string);

            foreach ($charcode as $char) {
                $char = preg_replace('/\W0/s', '', $char);

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
            $charcode  = explode('\\', preg_replace('/\s/', '', implode(',', $matches[0])));

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
            $charcode  = explode('\\', preg_replace('/[ux]/', '', implode(',', $matches[0])));

            foreach (array_map('hexdec', array_filter($charcode)) as $char) {
                if (20 <= $char && $char <= 127) {
                    $converted .= chr((int) $char);
                }
            }
            $value .= "\n" . $converted;
        }

        return $value;
    }

    /**
     * Eliminate JS regex modifiers
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertJSRegexModifiers($value)
    {
        return preg_replace('/\/[gim]+/', '/', $value);
    }

    /**
     * Converts from hex/dec entities
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertEntities($value)
    {
        $converted = null;

        //deal with double encoded payload
        $value = preg_replace('/&amp;/', '&', $value);

        if (preg_match('/&#x?[\w]+/ms', $value)) {
            $converted = preg_replace('/(&#x?[\w]{2}\d?);?/ms', '$1;', $value);
            $converted = html_entity_decode($converted, ENT_QUOTES, 'UTF-8');
            $value    .= "\n" . str_replace(';;', ';', $converted);
        }

        // normalize obfuscated protocol handlers
        $value = preg_replace(
            '/(?:j\s*a\s*v\s*a\s*s\s*c\s*r\s*i\s*p\s*t\s*:)|(d\s*a\s*t\s*a\s*:)/ms',
            'javascript:',
            $value
        );

        return $value;
    }

    /**
     * Normalize quotes
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertQuotes($value)
    {
        // normalize different quotes to "
        $pattern = array('\'', '`', '´', '’', '‘');
        $value   = str_replace($pattern, '"', $value);

        //make sure harmless quoted strings don't generate false alerts
        $value = preg_replace('/^"([^"=\\!><~]+)"$/', '$1', $value);

        return $value;
    }

    /**
     * Converts SQLHEX to plain text
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromSQLHex($value)
    {
        $matches = array();
        if (preg_match_all('/(?:(?:\A|[^\d])0x[a-f\d]{3,}[a-f\d]*)+/im', $value, $matches)) {
            foreach ($matches[0] as $match) {
                $converted = '';
                foreach (str_split($match, 2) as $hex_index) {
                    if (preg_match('/[a-f\d]{2,3}/i', $hex_index)) {
                        $converted .= chr((int) hexdec($hex_index));
                    }
                }
                $value = str_replace($match, $converted, $value);
            }
        }
        // take care of hex encoded ctrl chars
        $value = preg_replace('/0x\d+/m', ' 1 ', $value);

        return $value;
    }

    /**
     * Converts basic SQL keywords and obfuscations
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromSQLKeywords($value)
    {
        $pattern = array(
            '/(?:is\s+null)|(like\s+null)|' .
            '(?:(?:^|\W)in[+\s]*\([\s\d"]+[^()]*\))/ims'
        );
        $value   = preg_replace($pattern, '"=0', $value);

        $value   = preg_replace('/[^\w\)]+\s*like\s*[^\w\s]+/ims', '1" OR "1"', $value);
        $value   = preg_replace('/null([,"\s])/ims', '0$1', $value);
        $value   = preg_replace('/\d+\./ims', ' 1', $value);
        $value   = preg_replace('/,null/ims', ',0', $value);
        $value   = preg_replace('/(?:between)/ims', 'or', $value);
        $value   = preg_replace('/(?:and\s+\d+\.?\d*)/ims', '', $value);
        $value   = preg_replace('/(?:\s+and\s+)/ims', ' or ', $value);

        $pattern = array(
            '/(?:not\s+between)|(?:is\s+not)|(?:not\s+in)|' .
            '(?:xor|<>|rlike(?:\s+binary)?)|' .
            '(?:regexp\s+binary)|' .
            '(?:sounds\s+like)/ims'
        );
        $value   = preg_replace($pattern, '!', $value);
        $value   = preg_replace('/"\s+\d/', '"', $value);
        $value   = preg_replace('/(\W)div(\W)/ims', '$1 OR $2', $value);
        $value   = preg_replace('/\/(?:\d+|null)/', '', $value);

        return $value;
    }

    /**
     * Detects nullbytes and controls chars via ord()
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromControlChars($value)
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
        $value = urldecode(
            preg_replace(
                '/(?:%E(?:2|3)%8(?:0|1)%(?:A|8|9)\w|%EF%BB%BF|%EF%BF%BD)|(?:&#(?:65|8)\d{3};?)/i',
                '',
                urlencode($value)
            )
        );
        $value = urlencode($value);
        $value = preg_replace('/(?:%F0%80%BE)/i', '>', $value);
        $value = preg_replace('/(?:%F0%80%BC)/i', '<', $value);
        $value = preg_replace('/(?:%F0%80%A2)/i', '"', $value);
        $value = preg_replace('/(?:%F0%80%A7)/i', '\'', $value);
        $value = urldecode($value);

        $value = preg_replace('/(?:%ff1c)/', '<', $value);
        $value = preg_replace('/(?:&[#x]*(200|820|200|820|zwn?j|lrm|rlm)\w?;?)/i', '', $value);
        $value = preg_replace(
            '/(?:&#(?:65|8)\d{3};?)|' .
            '(?:&#(?:56|7)3\d{2};?)|' .
            '(?:&#x(?:fe|20)\w{2};?)|' .
            '(?:&#x(?:d[c-f])\w{2};?)/i',
            '',
            $value
        );

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

    /**
     * This method matches and translates base64 strings and fragments
     * used in data URIs
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromNestedBase64($value)
    {
        $matches = array();
        preg_match_all('/(?:^|[,&?])\s*([a-z0-9]{50,}=*)(?:\W|$)/im', $value, $matches);

        foreach ($matches[1] as $item) {
            if (!preg_match('/[a-f0-9]{32}/i', $item)) {
                $base64_item = base64_decode($item);
                $value = str_replace($item, $base64_item, $value);
            }
        }

        return $value;
    }

    /**
     * Detects nullbytes and controls chars via ord()
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromOutOfRangeChars($value)
    {
        $values = str_split($value);
        foreach ($values as $item) {
            if (ord($item) >= 127) {
                $value = str_replace($item, ' ', $value);
            }
        }

        return $value;
    }

    /**
     * Strip XML patterns
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromXML($value)
    {
        $converted = strip_tags($value);
        if (!$converted || $converted === $value) {
            return $value;
        } else {
            return $value . "\n" . $converted;
        }
    }

    /**
     * This method converts JS unicode code points to
     * regular characters
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromJSUnicode($value)
    {
        $matches = array();
        preg_match_all('/\\\u[0-9a-f]{4}/ims', $value, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                $chr = chr((int) hexdec(substr($match, 2, 4)));
                $value = str_replace($match, $chr, $value);
            }
            $value .= "\n\u0001";
        }

        return $value;
    }

    /**
     * Converts relevant UTF-7 tags to UTF-8
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromUTF7($value)
    {
        if (preg_match('/\+A\w+-?/m', $value)) {
            if (function_exists('mb_convert_encoding')) {
                if (version_compare(PHP_VERSION, '5.2.8', '<')) {
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
                $schemes = array(
                    '+ACI-'      => '"',
                    '+ADw-'      => '<',
                    '+AD4-'      => '>',
                    '+AFs-'      => '[',
                    '+AF0-'      => ']',
                    '+AHs-'      => '{',
                    '+AH0-'      => '}',
                    '+AFw-'      => '\\',
                    '+ADs-'      => ';',
                    '+ACM-'      => '#',
                    '+ACY-'      => '&',
                    '+ACU-'      => '%',
                    '+ACQ-'      => '$',
                    '+AD0-'      => '=',
                    '+AGA-'      => '`',
                    '+ALQ-'      => '"',
                    '+IBg-'      => '"',
                    '+IBk-'      => '"',
                    '+AHw-'      => '|',
                    '+ACo-'      => '*',
                    '+AF4-'      => '^',
                    '+ACIAPg-'   => '">',
                    '+ACIAPgA8-' => '">'
                );

                $value = str_ireplace(
                    array_keys($schemes),
                    array_values($schemes),
                    $value
                );
            }
        }

        return $value;
    }

    /**
     * Converts basic concatenations
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromConcatenated($value)
    {
        //normalize remaining backslashes
        if ($value != preg_replace('/(\w)\\\/', "$1", $value)) {
            $value .= preg_replace('/(\w)\\\/', "$1", $value);
        }

        $compare = stripslashes($value);

        $pattern = array(
            '/(?:<\/\w+>\+<\w+>)/s',
            '/(?:":\d+[^"[]+")/s',
            '/(?:"?"\+\w+\+")/s',
            '/(?:"\s*;[^"]+")|(?:";[^"]+:\s*")/s',
            '/(?:"\s*(?:;|\+).{8,18}:\s*")/s',
            '/(?:";\w+=)|(?:!""&&")|(?:~)/s',
            '/(?:"?"\+""?\+?"?)|(?:;\w+=")|(?:"[|&]{2,})/s',
            '/(?:"\s*\W+")/s',
            '/(?:";\w\s*\+=\s*\w?\s*")/s',
            '/(?:"[|&;]+\s*[^|&\n]*[|&]+\s*"?)/s',
            '/(?:";\s*\w+\W+\w*\s*[|&]*")/s',
            '/(?:"\s*"\s*\.)/s',
            '/(?:\s*new\s+\w+\s*[+",])/',
            '/(?:(?:^|\s+)(?:do|else)\s+)/',
            '/(?:[{(]\s*new\s+\w+\s*[)}])/',
            '/(?:(this|self)\.)/',
            '/(?:undefined)/',
            '/(?:in\s+)/'
        );

        // strip out concatenations
        $converted = preg_replace($pattern, '', $compare);

        //strip object traversal
        $converted = preg_replace('/\w(\.\w\()/', "$1", $converted);

        // normalize obfuscated method calls
        $converted = preg_replace('/\)\s*\+/', ")", $converted);

        //convert JS special numbers
        $converted = preg_replace(
            '/(?:\(*[.\d]e[+-]*[^a-z\W]+\)*)|(?:NaN|Infinity)\W/ims',
            '1',
            $converted
        );

        if ($converted && ($compare != $converted)) {
            $value .= "\n" . $converted;
        }

        return $value;
    }

    /**
     * This method collects and decodes proprietary encoding types
     *
     * @param string $value the value to convert
     *
     * @static
     * @return string
     */
    public static function convertFromProprietaryEncodings($value)
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

  /**
   * This method removes encoded sql # comments
   *
   * @param string $value the value to convert
   *
   * @static
   * @return string
   */
    public static function convertFromUrlencodeSqlComment($value)
    {
        if (preg_match_all('/(?:\%23.*?\%0a)/im',$value,$matches)){
            $converted = $value;
            foreach($matches[0] as $match){
                $converted = str_replace($match,' ',$converted);
            }
            $value .= "\n" . $converted;
        }
        return $value;
    }

    /**
     * This method is the centrifuge prototype
     *
     * @param string  $value   the value to convert
     * @param Monitor $monitor the monitor object
     *
     * @static
     * @return string
     */
    public static function runCentrifuge($value, ?Monitor $monitor = null)
    {
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

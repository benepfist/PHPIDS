<?php

namespace IDS\Converters;

use IDS\ConverterInterface;

class UrlencodeSqlCommentConverter implements ConverterInterface
{
    public function convert(string $value): string
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
}

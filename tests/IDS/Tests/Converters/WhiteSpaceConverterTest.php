<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\WhiteSpaceConverter;

class WhiteSpaceConverterTest extends TestCase
{
    private WhiteSpaceConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new WhiteSpaceConverter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
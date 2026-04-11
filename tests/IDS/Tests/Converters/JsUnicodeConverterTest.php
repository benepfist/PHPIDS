<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\JsUnicodeConverter;

class JsUnicodeConverterTest extends TestCase
{
    private JsUnicodeConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new JsUnicodeConverter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
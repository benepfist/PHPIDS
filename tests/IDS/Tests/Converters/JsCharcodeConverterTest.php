<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\JsCharcodeConverter;

class JsCharcodeConverterTest extends TestCase
{
    private JsCharcodeConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new JsCharcodeConverter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
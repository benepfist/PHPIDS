<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\JsRegexModifiersConverter;

class JsRegexModifiersConverterTest extends TestCase
{
    private JsRegexModifiersConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new JsRegexModifiersConverter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
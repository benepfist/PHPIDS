<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\NestedBase64Converter;

class NestedBase64ConverterTest extends TestCase
{
    private NestedBase64Converter $converter;

    protected function setUp(): void
    {
        $this->converter = new NestedBase64Converter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\UTF7Converter;

class UTF7ConverterTest extends TestCase
{
    private UTF7Converter $converter;

    protected function setUp(): void
    {
        $this->converter = new UTF7Converter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
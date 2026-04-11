<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\XMLConverter;

class XMLConverterTest extends TestCase
{
    private XMLConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new XMLConverter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
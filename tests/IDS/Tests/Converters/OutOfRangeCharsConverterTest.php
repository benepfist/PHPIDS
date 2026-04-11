<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\OutOfRangeCharsConverter;

class OutOfRangeCharsConverterTest extends TestCase
{
    private OutOfRangeCharsConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new OutOfRangeCharsConverter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
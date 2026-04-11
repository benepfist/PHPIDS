<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\ControlCharsConverter;

class ControlCharsConverterTest extends TestCase
{
    private ControlCharsConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new ControlCharsConverter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
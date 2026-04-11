<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\ConcatenatedConverter;

class ConcatenatedConverterTest extends TestCase
{
    private ConcatenatedConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new ConcatenatedConverter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\ProprietaryEncodingsConverter;

class ProprietaryEncodingsConverterTest extends TestCase
{
    private ProprietaryEncodingsConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new ProprietaryEncodingsConverter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
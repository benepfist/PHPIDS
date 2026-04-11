<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\QuotesConverter;

class QuotesConverterTest extends TestCase
{
    private QuotesConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new QuotesConverter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\SQLKeywordsConverter;

class SQLKeywordsConverterTest extends TestCase
{
    private SQLKeywordsConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new SQLKeywordsConverter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
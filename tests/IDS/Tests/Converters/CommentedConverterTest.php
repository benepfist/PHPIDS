<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\CommentedConverter;

class CommentedConverterTest extends TestCase
{
    private CommentedConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new CommentedConverter();
    }

    public function testConvertBasic(): void
    {
        $input = "dummy payload";
        $expected = $this->converter->convert($input);
        $this->assertIsString($expected);
    }
}
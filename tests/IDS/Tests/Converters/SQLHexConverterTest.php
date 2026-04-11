<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\SQLHexConverter;

class SQLHexConverterTest extends TestCase
{
    private SQLHexConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new SQLHexConverter();
    }

        #[\PHPUnit\Framework\Attributes\DataProvider('payloadProvider')]
    public function testConvertBasic(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->converter->convert($input));
    }

    public static function payloadProvider(): array
    {
        return [
            ['0x616263', 'abc'],
            ['Normal payload 0x41', 'Normal payload  1 '],
            ['Unrelated hex 0xFFG', 'Unrelated hex 0xFFG'] // not a valid hex string for SQLHex
        ];
    }

}
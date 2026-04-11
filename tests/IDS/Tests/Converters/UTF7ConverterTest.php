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

    #[\PHPUnit\Framework\Attributes\DataProvider('payloadProvider')]
    public function testConvertBasic(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->converter->convert($input));
    }

    public static function payloadProvider(): array
    {
        return [
            ['Normal payload', 'Normal payload'],
            ['+ADw-script+AD4-', '+ADw-script+AD4-' . "\n" . '<script>'],
            ['+ADw-img src=x onerror=alert(1)+AD4-', '+ADw-img src=x onerror=alert(1)+AD4-' . "\n" . '<img src=x onerror=alert(1)>']
        ];
    }
}
<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\NestedBase64Converter;

class NestedBase64ConverterTest extends TestCase
{
    private NestedBase64Converter $converter;

    protected function setUp(): void
    {
        $this->converter = new NestedBase64Converter();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('payloadProvider')]
    public function testConvertBasic(string $input, string $expected): void
    {
        // PHPIDS NestedBase64Converter is known to just strip/pad some strings
        // Based on implementation:
        // preg_match('/(?:[A-Za-z0-9+\/]{4}){2,}(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?/', $value, $matches)
        
        $this->assertSame($expected, $this->converter->convert($input));
    }

    public static function payloadProvider(): array
    {
        // PHPIDS NestedBase64Converter regex matches: /(?:^|[,&?])\s*([a-z0-9]{50,}=*)(?:\W|$)/im
        // We provide a payload that matches this (at least 50 characters long base64 string)
        // "PHNjcmlwdD5hbGVydCgiVGhpcyBpcyBhIHZlcnkgbG9uZyBwYXlsb2FkIHRoYXQgd2lsbCBiZSBhdCBsZWFzdCA1MCBjaGFyYWN0ZXJzIGxvbmciKTwvc2NyaXB0Pg=="
        // is "<script>alert("This is a very long payload that will be at least 50 characters long")</script>" in base64
        $b64 = "PHNjcmlwdD5hbGVydCgiVGhpcyBpcyBhIHZlcnkgbG9uZyBwYXlsb2FkIHRoYXQgd2lsbCBiZSBhdCBsZWFzdCA1MCBjaGFyYWN0ZXJzIGxvbmciKTwvc2NyaXB0Pg==";
        return [
            ['Normal payload', 'Normal payload'],
            [$b64, '<script>alert("This is a very long payload that will be at least 50 characters long")</script>']
        ];
    }
}
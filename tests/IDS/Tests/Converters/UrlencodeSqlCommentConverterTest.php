<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\UrlencodeSqlCommentConverter;

class UrlencodeSqlCommentConverterTest extends TestCase
{
    private UrlencodeSqlCommentConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new UrlencodeSqlCommentConverter();
    }

        #[\PHPUnit\Framework\Attributes\DataProvider('payloadProvider')]
    public function testConvertBasic(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->converter->convert($input));
    }

    public static function payloadProvider(): array
    {
        return [
            ['SELECT * FROM table %23 SQL comment %0a AND 1=1', "SELECT * FROM table %23 SQL comment %0a AND 1=1\nSELECT * FROM table   AND 1=1"],
            ['Normal payload', 'Normal payload']
        ];
    }

}
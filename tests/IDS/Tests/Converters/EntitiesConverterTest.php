<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\EntitiesConverter;

class EntitiesConverterTest extends TestCase
{
    private EntitiesConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new EntitiesConverter();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('payloadProvider')]
    public function testConvertBasic(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->converter->convert($input));
    }

    public static function payloadProvider(): array
    {
        return [
            ['Normal string', 'Normal string'],
            ['&#x61;&#x62;', '&#x61;&#x62;'."\n".'ab'], // original script appends decoded string
            ['&amp;', '&']
        ];
    }

}
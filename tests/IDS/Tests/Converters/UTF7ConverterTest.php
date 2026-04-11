<?php

namespace IDS\Tests\Converters;

use PHPUnit\Framework\TestCase;
use IDS\Converters\UTF7Converter;

class UTF7ConverterTest extends TestCase
{
    public static bool $forceFunctionMissing = false;
    public static bool $forceVersionCompareNotLessThan = false;
    public static ?string $mockConvertedValue = null;

    private UTF7Converter $converter;

    public static function setUpBeforeClass(): void
    {
        self::registerUtf7Shims();
    }

    protected function setUp(): void
    {
        $this->converter = new UTF7Converter();
    }

    protected function tearDown(): void
    {
        self::$forceFunctionMissing = false;
        self::$forceVersionCompareNotLessThan = false;
        self::$mockConvertedValue = null;
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

    public function testConvertUsesStaticReplacementTableWithoutMbstring(): void
    {
        self::$forceFunctionMissing = true;

        $this->assertSame('<script>', $this->converter->convert('+ADw-script+AD4-'));
    }

    public function testConvertSkipsAsciiFilteringForPhp84AndAboveBranch(): void
    {
        self::$forceVersionCompareNotLessThan = true;
        self::$mockConvertedValue = '<script>';

        $input = "µ+ADw-script+AD4-";

        $this->assertSame($input . "\n" . '<script>', $this->converter->convert($input));
    }

    private static function registerUtf7Shims(): void
    {
        if (\function_exists('IDS\\Converters\\function_exists')) {
            return;
        }

        eval(<<<'PHP'
namespace IDS\Converters;

function function_exists(string $name): bool
{
    if ($name === 'mb_convert_encoding' && \IDS\Tests\Converters\UTF7ConverterTest::$forceFunctionMissing) {
        return false;
    }

    return \function_exists($name);
}

function version_compare(string $version1, string $version2, ?string $operator = null): int|bool
{
    if (\IDS\Tests\Converters\UTF7ConverterTest::$forceVersionCompareNotLessThan) {
        return $operator === null ? 1 : false;
    }

    return \version_compare($version1, $version2, $operator);
}

function mb_convert_encoding(array|string $string, string $to_encoding, array|string|null $from_encoding = null): string
{
    if (\IDS\Tests\Converters\UTF7ConverterTest::$mockConvertedValue !== null) {
        return \IDS\Tests\Converters\UTF7ConverterTest::$mockConvertedValue;
    }

    return \mb_convert_encoding($string, $to_encoding, $from_encoding);
}
PHP);
    }
}

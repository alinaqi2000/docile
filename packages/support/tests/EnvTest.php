<?php

declare(strict_types=1);

namespace Docile\Support\Tests;

use Docile\Support\Env;
use Docile\Support\Exception\MissingEnvException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Env::class)]
#[CoversClass(MissingEnvException::class)]
final class EnvTest extends TestCase
{
    /** @var array<mixed> */
    private array $originalEnv = [];

    protected function setUp(): void
    {
        // Snapshot the current $_ENV so we can restore it after each test
        $this->originalEnv = $_ENV;
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnv;

        // Remove any keys we may have added
        foreach (array_diff_key($_ENV, $this->originalEnv) as $key => $value) {
            unset($_ENV[$key]);
        }
    }

    // ---------------------------------------------------------------- get

    public function testGetReturnsValue(): void
    {
        $_ENV['TEST_KEY'] = 'hello';
        self::assertSame('hello', Env::get('TEST_KEY'));
    }

    public function testGetReturnsDefaultWhenMissing(): void
    {
        unset($_ENV['MISSING_KEY']);
        self::assertNull(Env::get('MISSING_KEY'));
        self::assertSame('default', Env::get('MISSING_KEY', 'default'));
    }

    // ---------------------------------------------------------------- string

    public function testStringCoercesValue(): void
    {
        $_ENV['STR_KEY'] = '42';
        self::assertSame('42', Env::string('STR_KEY'));
    }

    public function testStringReturnsDefaultWhenMissing(): void
    {
        unset($_ENV['STR_MISSING']);
        self::assertSame('', Env::string('STR_MISSING'));
        self::assertSame('fallback', Env::string('STR_MISSING', 'fallback'));
    }

    // ---------------------------------------------------------------- int

    public function testIntCoercesValue(): void
    {
        $_ENV['INT_KEY'] = '42';
        self::assertSame(42, Env::int('INT_KEY'));
    }

    public function testIntReturnsDefaultWhenMissing(): void
    {
        unset($_ENV['INT_MISSING']);
        self::assertSame(0, Env::int('INT_MISSING'));
        self::assertSame(99, Env::int('INT_MISSING', 99));
    }

    // ---------------------------------------------------------------- bool

    /** @return list<array{string, bool}> */
    public static function boolTruthyProvider(): array
    {
        return [
            ['true', true],
            ['1', true],
            ['yes', true],
            ['on', true],
            ['TRUE', true],
            ['YES', true],
        ];
    }

    #[DataProvider('boolTruthyProvider')]
    public function testBoolTruthyValues(string $rawValue, bool $expected): void
    {
        $_ENV['BOOL_KEY'] = $rawValue;
        self::assertSame($expected, Env::bool('BOOL_KEY'));
    }

    /** @return list<array{string, bool}> */
    public static function boolFalsyProvider(): array
    {
        return [
            ['false', false],
            ['0', false],
            ['no', false],
            ['off', false],
            ['', false],
        ];
    }

    #[DataProvider('boolFalsyProvider')]
    public function testBoolFalsyValues(string $rawValue, bool $expected): void
    {
        $_ENV['BOOL_KEY'] = $rawValue;
        self::assertSame($expected, Env::bool('BOOL_KEY'));
    }

    public function testBoolReturnsDefaultWhenMissing(): void
    {
        unset($_ENV['BOOL_MISSING']);
        self::assertFalse(Env::bool('BOOL_MISSING'));
        self::assertTrue(Env::bool('BOOL_MISSING', true));
    }

    public function testBoolWithNativeBoolValue(): void
    {
        $_ENV['BOOL_NATIVE'] = true;
        self::assertTrue(Env::bool('BOOL_NATIVE'));

        $_ENV['BOOL_NATIVE'] = false;
        self::assertFalse(Env::bool('BOOL_NATIVE'));
    }

    // ---------------------------------------------------------------- required

    public function testRequiredReturnsValue(): void
    {
        $_ENV['REQUIRED_KEY'] = 'value';
        self::assertSame('value', Env::required('REQUIRED_KEY'));
    }

    public function testRequiredThrowsWhenMissing(): void
    {
        unset($_ENV['REQUIRED_MISSING']);

        $this->expectException(MissingEnvException::class);
        $this->expectExceptionMessage('REQUIRED_MISSING');

        Env::required('REQUIRED_MISSING');
    }

    public function testRequiredMessageContainsKey(): void
    {
        unset($_ENV['MY_SECRET']);

        try {
            Env::required('MY_SECRET');
            self::fail('Expected exception not thrown');
        } catch (MissingEnvException $e) {
            self::assertStringContainsString('MY_SECRET', $e->getMessage());
        }
    }
}

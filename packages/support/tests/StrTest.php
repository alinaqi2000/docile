<?php

declare(strict_types=1);

namespace Docile\Support\Tests;

use Docile\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Str::class)]
final class StrTest extends TestCase
{
    // ---------------------------------------------------------------- studly

    /** @return list<array{string, string}> */
    public static function studlyProvider(): array
    {
        return [
            ['hello_world', 'HelloWorld'],
            ['hello-world', 'HelloWorld'],
            ['hello world', 'HelloWorld'],
            ['foo_bar_baz', 'FooBarBaz'],
            ['already', 'Already'],
            ['', ''],
        ];
    }

    #[DataProvider('studlyProvider')]
    public function testStudly(string $input, string $expected): void
    {
        self::assertSame($expected, Str::studly($input));
    }

    // ---------------------------------------------------------------- camel

    /** @return list<array{string, string}> */
    public static function camelProvider(): array
    {
        return [
            ['hello_world', 'helloWorld'],
            ['foo_bar_baz', 'fooBarBaz'],
            ['FooBar', 'fooBar'],
            ['', ''],
        ];
    }

    #[DataProvider('camelProvider')]
    public function testCamel(string $input, string $expected): void
    {
        self::assertSame($expected, Str::camel($input));
    }

    // ---------------------------------------------------------------- snake

    /** @return list<array{string, string}> */
    public static function snakeProvider(): array
    {
        return [
            ['HelloWorld', 'hello_world'],
            ['helloWorld', 'hello_world'],
            ['FooBarBaz', 'foo_bar_baz'],
            ['hello_world', 'hello_world'],
            ['', ''],
        ];
    }

    #[DataProvider('snakeProvider')]
    public function testSnake(string $input, string $expected): void
    {
        self::assertSame($expected, Str::snake($input));
    }

    // ---------------------------------------------------------------- kebab

    /** @return list<array{string, string}> */
    public static function kebabProvider(): array
    {
        return [
            ['HelloWorld', 'hello-world'],
            ['helloWorld', 'hello-world'],
            ['FooBarBaz', 'foo-bar-baz'],
            ['hello_world', 'hello-world'],
            ['', ''],
        ];
    }

    #[DataProvider('kebabProvider')]
    public function testKebab(string $input, string $expected): void
    {
        self::assertSame($expected, Str::kebab($input));
    }

    // ---------------------------------------------------------------- title

    public function testTitle(): void
    {
        self::assertSame('Hello World', Str::title('hello world'));
        self::assertSame('Foo Bar Baz', Str::title('foo bar baz'));
        self::assertSame('', Str::title(''));
    }

    // ---------------------------------------------------------------- slug

    public function testSlugDefault(): void
    {
        self::assertSame('hello-world', Str::slug('Hello, World!'));
        self::assertSame('foo-bar', Str::slug('  foo   bar  '));
        self::assertSame('hello-world', Str::slug('Hello World'));
    }

    public function testSlugCustomSeparator(): void
    {
        self::assertSame('hello_world', Str::slug('Hello World', '_'));
    }

    public function testSlugStripsLeadingTrailingSeparator(): void
    {
        self::assertSame('hello', Str::slug('-hello-', '-'));
    }

    // ---------------------------------------------------------------- startsWith

    public function testStartsWithString(): void
    {
        self::assertTrue(Str::startsWith('hello world', 'hello'));
        self::assertFalse(Str::startsWith('hello world', 'world'));
    }

    public function testStartsWithArray(): void
    {
        self::assertTrue(Str::startsWith('hello world', ['world', 'hello']));
        self::assertFalse(Str::startsWith('hello world', ['foo', 'bar']));
    }

    public function testStartsWithEmptyNeedleReturnsFalse(): void
    {
        self::assertFalse(Str::startsWith('hello', ''));
        self::assertFalse(Str::startsWith('hello', ['']));
    }

    // ---------------------------------------------------------------- endsWith

    public function testEndsWithString(): void
    {
        self::assertTrue(Str::endsWith('hello world', 'world'));
        self::assertFalse(Str::endsWith('hello world', 'hello'));
    }

    public function testEndsWithArray(): void
    {
        self::assertTrue(Str::endsWith('hello world', ['hello', 'world']));
        self::assertFalse(Str::endsWith('hello world', ['foo', 'bar']));
    }

    public function testEndsWithEmptyNeedleReturnsFalse(): void
    {
        self::assertFalse(Str::endsWith('hello', ''));
    }

    // ---------------------------------------------------------------- contains

    public function testContainsString(): void
    {
        self::assertTrue(Str::contains('hello world', 'lo wo'));
        self::assertFalse(Str::contains('hello world', 'xyz'));
    }

    public function testContainsArray(): void
    {
        self::assertTrue(Str::contains('hello world', ['xyz', 'world']));
        self::assertFalse(Str::contains('hello world', ['foo', 'bar']));
    }

    public function testContainsEmptyNeedleReturnsFalse(): void
    {
        self::assertFalse(Str::contains('hello', ''));
    }

    // ---------------------------------------------------------------- limit

    public function testLimitNoTruncation(): void
    {
        self::assertSame('hello', Str::limit('hello', 10));
    }

    public function testLimitExact(): void
    {
        self::assertSame('hello', Str::limit('hello', 5));
    }

    public function testLimitTruncates(): void
    {
        self::assertSame('hel...', Str::limit('hello world', 3));
    }

    public function testLimitCustomEnd(): void
    {
        self::assertSame('hel~', Str::limit('hello world', 3, '~'));
    }

    // ---------------------------------------------------------------- random

    public function testRandomLength(): void
    {
        self::assertSame(16, strlen(Str::random()));
        self::assertSame(32, strlen(Str::random(32)));
        self::assertSame(8, strlen(Str::random(8)));
    }

    public function testRandomIsAlphanumeric(): void
    {
        self::assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', Str::random(100));
    }

    public function testRandomProducesUniqueValues(): void
    {
        self::assertNotSame(Str::random(), Str::random());
    }

    // ---------------------------------------------------------------- uuid

    public function testUuidFormat(): void
    {
        $uuid = Str::uuid();
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
    }

    public function testUuidProducesUniqueValues(): void
    {
        self::assertNotSame(Str::uuid(), Str::uuid());
    }

    // ---------------------------------------------------------------- ulid

    public function testUlidLength(): void
    {
        self::assertSame(26, strlen(Str::ulid()));
    }

    public function testUlidCharacterSet(): void
    {
        // Crockford Base32: digits + uppercase letters except I, L, O, U
        self::assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', Str::ulid());
    }

    public function testUlidIsLexicographicallySortable(): void
    {
        // Small sleep to ensure different timestamps (at ms resolution)
        $a = Str::ulid();
        usleep(2000);
        $b = Str::ulid();

        self::assertLessThan($b, $a);
    }

    // ---------------------------------------------------------------- mask

    public function testMaskFromStart(): void
    {
        self::assertSame('***lo', Str::mask('hello', '*', 0, 3));
    }

    public function testMaskToEnd(): void
    {
        self::assertSame('he***', Str::mask('hello', '*', 2));
    }

    public function testMaskFullString(): void
    {
        self::assertSame('*****', Str::mask('hello', '*', 0));
    }

    public function testMaskNegativeStart(): void
    {
        self::assertSame('hel**', Str::mask('hello', '*', -2));
    }

    public function testMaskZeroLength(): void
    {
        self::assertSame('hello', Str::mask('hello', '*', 0, 0));
    }

    public function testMaskCustomChar(): void
    {
        self::assertSame('hXXXo', Str::mask('hello', 'X', 1, 3));
    }
}

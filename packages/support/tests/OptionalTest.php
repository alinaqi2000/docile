<?php

declare(strict_types=1);

namespace Docile\Support\Tests;

use Docile\Support\Exception\OptionalIsNoneException;
use Docile\Support\Optional;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Optional::class)]
#[CoversClass(OptionalIsNoneException::class)]
final class OptionalTest extends TestCase
{
    // ---------------------------------------------------------------- some

    public function testSomeIsSome(): void
    {
        $opt = Optional::some(42);
        self::assertTrue($opt->isSome());
        self::assertFalse($opt->isNone());
    }

    public function testSomeHoldsValue(): void
    {
        $opt = Optional::some('hello');
        self::assertSame('hello', $opt->get());
    }

    public function testSomeWithNullValue(): void
    {
        // some(null) is valid — it holds a null value, it's NOT None
        $opt = Optional::some(null);
        self::assertTrue($opt->isSome());
        self::assertNull($opt->get());
    }

    // ---------------------------------------------------------------- none

    public function testNoneIsNone(): void
    {
        $opt = Optional::none();
        self::assertTrue($opt->isNone());
        self::assertFalse($opt->isSome());
    }

    public function testNoneGetThrows(): void
    {
        $this->expectException(OptionalIsNoneException::class);

        Optional::none()->get();
    }

    public function testNoneExceptionMessage(): void
    {
        $message = null;

        try {
            Optional::none()->get();
        } catch (OptionalIsNoneException $e) {
            $message = $e->getMessage();
        }

        self::assertIsString($message);
        self::assertStringContainsString('None', $message);
    }

    // ---------------------------------------------------------------- getOrElse

    public function testGetOrElseSome(): void
    {
        self::assertSame(42, Optional::some(42)->getOrElse(0));
    }

    public function testGetOrElseNone(): void
    {
        self::assertSame(0, Optional::none()->getOrElse(0));
    }

    public function testGetOrElseNoneWithNull(): void
    {
        self::assertNull(Optional::none()->getOrElse(null));
    }

    // ---------------------------------------------------------------- map

    public function testMapOnSome(): void
    {
        $result = Optional::some(5)->map(fn (int $v): int => $v * 2);
        self::assertTrue($result->isSome());
        self::assertSame(10, $result->get());
    }

    public function testMapOnNone(): void
    {
        $result = Optional::none()->map(fn (mixed $v): string => 'should not run');
        self::assertTrue($result->isNone());
    }

    public function testMapCanChangeType(): void
    {
        $result = Optional::some(42)->map(fn (int $v): string => "Value is $v");
        self::assertSame('Value is 42', $result->get());
    }

    public function testMapChaining(): void
    {
        $result = Optional::some(1)
            ->map(fn (int $v): int => $v + 1)
            ->map(fn (int $v): int => $v * 3)
            ->map(fn (int $v): string => "result: $v");

        self::assertSame('result: 6', $result->get());
    }

    // ---------------------------------------------------------------- flatMap

    public function testFlatMapOnSome(): void
    {
        $result = Optional::some(5)->flatMap(fn (int $v): Optional => Optional::some($v * 2));
        self::assertTrue($result->isSome());
        self::assertSame(10, $result->get());
    }

    public function testFlatMapOnNone(): void
    {
        $called = false;
        $result = Optional::none()->flatMap(function (mixed $v) use (&$called): Optional {
            $called = true;

            return Optional::some($v);
        });

        self::assertFalse($called);
        self::assertTrue($result->isNone());
    }

    public function testFlatMapCanReturnNone(): void
    {
        $result = Optional::some(5)->flatMap(fn (int $v): Optional => $v > 10 ? Optional::some($v) : Optional::none());
        self::assertTrue($result->isNone());
    }

    public function testFlatMapChaining(): void
    {
        $result = Optional::some(10)
            ->flatMap(fn (int $v): Optional => $v > 5 ? Optional::some($v + 1) : Optional::none())
            ->flatMap(fn (int $v): Optional => Optional::some($v * 2));

        self::assertSame(22, $result->get());
    }

    public function testFlatMapChainingBreaksOnNone(): void
    {
        $result = Optional::some(3)
            ->flatMap(fn (int $v): Optional => $v > 5 ? Optional::some($v) : Optional::none())
            ->flatMap(fn (int $v): Optional => Optional::some($v * 100));

        self::assertTrue($result->isNone());
    }
}

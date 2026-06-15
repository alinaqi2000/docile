<?php

declare(strict_types=1);

namespace Docile\Support\Tests;

use Docile\Support\Arr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Arr::class)]
final class ArrTest extends TestCase
{
    // ---------------------------------------------------------------- get

    public function testGetTopLevelKey(): void
    {
        self::assertSame('bar', Arr::get(['foo' => 'bar'], 'foo'));
    }

    public function testGetDotNotation(): void
    {
        self::assertSame('baz', Arr::get(['foo' => ['bar' => 'baz']], 'foo.bar'));
    }

    public function testGetMissingKeyReturnsDefault(): void
    {
        self::assertNull(Arr::get(['foo' => 'bar'], 'missing'));
        self::assertSame('default', Arr::get(['foo' => 'bar'], 'missing', 'default'));
    }

    public function testGetMissingNestedKeyReturnsDefault(): void
    {
        self::assertNull(Arr::get(['foo' => ['bar' => 'baz']], 'foo.missing'));
    }

    public function testGetNestedMissingParentReturnsDefault(): void
    {
        self::assertSame('d', Arr::get([], 'a.b.c', 'd'));
    }

    // ---------------------------------------------------------------- set

    public function testSetTopLevel(): void
    {
        $arr = [];
        Arr::set($arr, 'foo', 'bar');
        self::assertSame(['foo' => 'bar'], $arr);
    }

    public function testSetDotNotation(): void
    {
        $arr = [];
        Arr::set($arr, 'foo.bar.baz', 'value');
        self::assertSame(['foo' => ['bar' => ['baz' => 'value']]], $arr);
    }

    public function testSetOverwritesExistingValue(): void
    {
        $arr = ['foo' => ['bar' => 'old']];
        Arr::set($arr, 'foo.bar', 'new');
        self::assertSame('new', Arr::get($arr, 'foo.bar'));
    }

    public function testSetCreatesIntermediateArrays(): void
    {
        $arr = ['foo' => 'scalar'];
        Arr::set($arr, 'foo.bar', 'value');
        self::assertSame(['bar' => 'value'], Arr::get($arr, 'foo'));
    }

    // ---------------------------------------------------------------- has

    public function testHasTopLevel(): void
    {
        self::assertTrue(Arr::has(['foo' => 'bar'], 'foo'));
        self::assertFalse(Arr::has(['foo' => 'bar'], 'baz'));
    }

    public function testHasDotNotation(): void
    {
        self::assertTrue(Arr::has(['foo' => ['bar' => 'baz']], 'foo.bar'));
        self::assertFalse(Arr::has(['foo' => ['bar' => 'baz']], 'foo.missing'));
    }

    public function testHasNullValueIsStillPresent(): void
    {
        self::assertTrue(Arr::has(['foo' => null], 'foo'));
    }

    // ---------------------------------------------------------------- forget

    public function testForgetTopLevel(): void
    {
        $arr = ['foo' => 'bar', 'baz' => 'qux'];
        Arr::forget($arr, 'foo');
        self::assertSame(['baz' => 'qux'], $arr);
    }

    public function testForgetDotNotation(): void
    {
        $arr = ['foo' => ['bar' => 'baz', 'keep' => 'me']];
        Arr::forget($arr, 'foo.bar');
        self::assertSame(['foo' => ['keep' => 'me']], $arr);
    }

    public function testForgetMissingKeyDoesNothing(): void
    {
        $arr = ['foo' => 'bar'];
        Arr::forget($arr, 'missing.key');
        self::assertSame(['foo' => 'bar'], $arr);
    }

    // ---------------------------------------------------------------- only

    public function testOnly(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        self::assertSame(['a' => 1, 'c' => 3], Arr::only($arr, ['a', 'c']));
    }

    public function testOnlyMissingKeysIgnored(): void
    {
        $arr = ['a' => 1, 'b' => 2];
        self::assertSame(['a' => 1], Arr::only($arr, ['a', 'x']));
    }

    // ---------------------------------------------------------------- except

    public function testExcept(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        self::assertSame(['b' => 2], Arr::except($arr, ['a', 'c']));
    }

    // ---------------------------------------------------------------- first

    public function testFirstNoCallback(): void
    {
        self::assertSame(1, Arr::first([1, 2, 3]));
    }

    public function testFirstWithCallback(): void
    {
        self::assertSame(3, Arr::first([1, 2, 3], fn (mixed $v): bool => $v > 2));
    }

    public function testFirstEmptyReturnsDefault(): void
    {
        self::assertNull(Arr::first([]));
        self::assertSame('default', Arr::first([], null, 'default'));
    }

    public function testFirstNoMatchReturnsDefault(): void
    {
        self::assertSame('x', Arr::first([1, 2, 3], fn (mixed $v): bool => $v > 10, 'x'));
    }

    // ---------------------------------------------------------------- last

    public function testLastNoCallback(): void
    {
        self::assertSame(3, Arr::last([1, 2, 3]));
    }

    public function testLastWithCallback(): void
    {
        self::assertSame(2, Arr::last([1, 2, 3], fn (mixed $v): bool => $v < 3));
    }

    public function testLastEmptyReturnsDefault(): void
    {
        self::assertNull(Arr::last([]));
        self::assertSame('d', Arr::last([], null, 'd'));
    }

    // ---------------------------------------------------------------- flatten

    public function testFlattenFullDepth(): void
    {
        self::assertSame([1, 2, 3, 4, 5], Arr::flatten([1, [2, [3, [4, 5]]]]));
    }

    public function testFlattenDepthOne(): void
    {
        self::assertSame([1, 2, [3, [4, 5]]], Arr::flatten([1, [2, [3, [4, 5]]]], 1));
    }

    public function testFlattenAlreadyFlat(): void
    {
        self::assertSame([1, 2, 3], Arr::flatten([1, 2, 3]));
    }

    // ---------------------------------------------------------------- dot

    public function testDot(): void
    {
        $result = Arr::dot(['foo' => ['bar' => 'baz', 'qux' => ['a' => 1]]]);
        self::assertSame(['foo.bar' => 'baz', 'foo.qux.a' => 1], $result);
    }

    public function testDotWithPrepend(): void
    {
        $result = Arr::dot(['foo' => 'bar'], 'prefix.');
        self::assertSame(['prefix.foo' => 'bar'], $result);
    }

    public function testDotPreservesEmptyArray(): void
    {
        // An empty nested array stays as-is (not dotted further)
        $result = Arr::dot(['foo' => []]);
        self::assertSame(['foo' => []], $result);
    }

    // ---------------------------------------------------------------- undot

    public function testUndot(): void
    {
        $result = Arr::undot(['foo.bar' => 'baz', 'foo.qux' => 'quux']);
        self::assertSame(['foo' => ['bar' => 'baz', 'qux' => 'quux']], $result);
    }

    public function testUndotRoundTrip(): void
    {
        $original = ['a' => ['b' => ['c' => 1], 'd' => 2]];
        self::assertSame($original, Arr::undot(Arr::dot($original)));
    }

    // ---------------------------------------------------------------- wrap

    public function testWrapNull(): void
    {
        self::assertSame([], Arr::wrap(null));
    }

    public function testWrapScalar(): void
    {
        self::assertSame([42], Arr::wrap(42));
        self::assertSame(['hello'], Arr::wrap('hello'));
    }

    public function testWrapArray(): void
    {
        self::assertSame([1, 2, 3], Arr::wrap([1, 2, 3]));
    }

    // ---------------------------------------------------------------- pluck

    public function testPluckValues(): void
    {
        $data = [
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
        ];
        self::assertSame(['Alice', 'Bob'], Arr::pluck($data, 'name'));
    }

    public function testPluckWithKey(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];
        self::assertSame([1 => 'Alice', 2 => 'Bob'], Arr::pluck($data, 'name', 'id'));
    }

    public function testPluckNestedDotNotation(): void
    {
        $data = [
            ['user' => ['name' => 'Alice']],
            ['user' => ['name' => 'Bob']],
        ];
        self::assertSame(['Alice', 'Bob'], Arr::pluck($data, 'user.name'));
    }
}

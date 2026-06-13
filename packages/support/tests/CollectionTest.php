<?php

declare(strict_types=1);

namespace Docile\Support\Tests;

use ArrayIterator;
use Docile\Support\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collection::class)]
final class CollectionTest extends TestCase
{
    // ---------------------------------------------------------------- construction

    public function testEmptyCollection(): void
    {
        $c = new Collection();
        self::assertSame([], $c->toArray());
        self::assertCount(0, $c);
    }

    public function testMakeFactory(): void
    {
        $c = Collection::make([1, 2, 3]);
        self::assertSame([1, 2, 3], $c->toArray());
    }

    // ---------------------------------------------------------------- map

    public function testMap(): void
    {
        $c = Collection::make([1, 2, 3])->map(fn (int $v): int => $v * 2);
        self::assertSame([2, 4, 6], $c->toArray());
    }

    public function testMapPreservesKeys(): void
    {
        $c = Collection::make(['a' => 1, 'b' => 2])->map(fn (int $v): int => $v + 10);
        self::assertSame(['a' => 11, 'b' => 12], $c->toArray());
    }

    public function testMapReceivesKey(): void
    {
        $keys = [];
        Collection::make(['x' => 1, 'y' => 2])->map(function (int $v, string $k) use (&$keys): int {
            $keys[] = $k;

            return $v;
        });
        self::assertSame(['x', 'y'], $keys);
    }

    // ---------------------------------------------------------------- filter

    public function testFilter(): void
    {
        $c = Collection::make([1, 2, 3, 4, 5])->filter(fn (int $v): bool => $v % 2 === 0);
        self::assertSame([1 => 2, 3 => 4], $c->toArray());
    }

    public function testFilterNoCallback(): void
    {
        $c = Collection::make([0, 1, '', 'hello', false, null, true])->filter();
        self::assertSame([1 => 1, 3 => 'hello', 6 => true], $c->toArray());
    }

    // ---------------------------------------------------------------- reduce

    public function testReduce(): void
    {
        $sum = Collection::make([1, 2, 3, 4, 5])->reduce(fn (int $carry, int $item): int => $carry + $item, 0);
        self::assertSame(15, $sum);
    }

    public function testReduceEmpty(): void
    {
        $result = Collection::make([])->reduce(fn (mixed $carry, mixed $item): mixed => $item, 'default');
        self::assertSame('default', $result);
    }

    // ---------------------------------------------------------------- each

    public function testEach(): void
    {
        $seen = [];
        $c = Collection::make([1, 2, 3]);
        $returned = $c->each(function (int $v) use (&$seen): void {
            $seen[] = $v;
        });

        self::assertSame([1, 2, 3], $seen);
        self::assertSame($c, $returned);
    }

    // ---------------------------------------------------------------- values / keys

    public function testValues(): void
    {
        $c = Collection::make(['a' => 1, 'b' => 2])->values();
        self::assertSame([0, 1], array_keys($c->toArray()));
    }

    public function testKeys(): void
    {
        $c = Collection::make(['a' => 1, 'b' => 2])->keys();
        self::assertSame(['a', 'b'], $c->toArray());
    }

    // ---------------------------------------------------------------- first / last

    public function testFirstNoCallback(): void
    {
        self::assertSame(1, Collection::make([1, 2, 3])->first());
    }

    public function testFirstWithCallback(): void
    {
        self::assertSame(3, Collection::make([1, 2, 3])->first(fn (int $v): bool => $v > 2));
    }

    public function testFirstEmptyReturnsDefault(): void
    {
        self::assertNull(Collection::make([])->first());
        self::assertSame('x', Collection::make([])->first(null, 'x'));
    }

    public function testLastNoCallback(): void
    {
        self::assertSame(3, Collection::make([1, 2, 3])->last());
    }

    public function testLastWithCallback(): void
    {
        self::assertSame(2, Collection::make([1, 2, 3])->last(fn (int $v): bool => $v < 3));
    }

    public function testLastEmptyReturnsDefault(): void
    {
        self::assertNull(Collection::make([])->last());
        self::assertSame('d', Collection::make([])->last(null, 'd'));
    }

    // ---------------------------------------------------------------- contains

    public function testContainsValue(): void
    {
        $c = Collection::make([1, 2, 3]);
        self::assertTrue($c->contains(2));
        self::assertFalse($c->contains(99));
    }

    public function testContainsClosure(): void
    {
        $c = Collection::make([1, 2, 3]);
        self::assertTrue($c->contains(fn (int $v): bool => $v > 2));
        self::assertFalse($c->contains(fn (int $v): bool => $v > 10));
    }

    // ---------------------------------------------------------------- sort

    public function testSortDefault(): void
    {
        $c = Collection::make([3, 1, 2])->sort();
        self::assertSame([1, 2, 3], array_values($c->toArray()));
    }

    public function testSortWithCallback(): void
    {
        $c = Collection::make([3, 1, 2])->sort(fn (int $a, int $b): int => $b - $a);
        self::assertSame([3, 2, 1], array_values($c->toArray()));
    }

    // ---------------------------------------------------------------- sortBy

    public function testSortByKey(): void
    {
        $items = [
            ['name' => 'Charlie', 'age' => 30],
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 28],
        ];
        $sorted = Collection::make($items)->sortBy('name')->values()->toArray();
        self::assertSame('Alice', $sorted[0]['name']);
        self::assertSame('Bob', $sorted[1]['name']);
        self::assertSame('Charlie', $sorted[2]['name']);
    }

    public function testSortByClosure(): void
    {
        $c = Collection::make([3, 1, 4, 1, 5])->sortBy(fn (int $v): int => $v);
        self::assertSame([1, 1, 3, 4, 5], array_values($c->toArray()));
    }

    // ---------------------------------------------------------------- groupBy

    public function testGroupByKey(): void
    {
        $items = [
            ['type' => 'a', 'val' => 1],
            ['type' => 'b', 'val' => 2],
            ['type' => 'a', 'val' => 3],
        ];
        $grouped = Collection::make($items)->groupBy('type');

        self::assertCount(2, $grouped);
        $first = $grouped->first();
        self::assertTrue($first instanceof Collection);
        $groupedArray = $grouped->toArray();
        self::assertArrayHasKey('a', $groupedArray);
        $aGroup = $groupedArray['a'];
        self::assertInstanceOf(Collection::class, $aGroup);
        self::assertCount(2, $aGroup);
    }

    public function testGroupByClosure(): void
    {
        $grouped = Collection::make([1, 2, 3, 4])->groupBy(fn (int $v): string => $v % 2 === 0 ? 'even' : 'odd');
        /** @var Collection<int, int> $even */
        $even = $grouped->toArray()['even'];
        /** @var Collection<int, int> $odd */
        $odd = $grouped->toArray()['odd'];
        self::assertCount(2, $even);
        self::assertCount(2, $odd);
    }

    // ---------------------------------------------------------------- chunk

    public function testChunk(): void
    {
        $chunks = Collection::make([1, 2, 3, 4, 5])->chunk(2);
        self::assertCount(3, $chunks);
        /** @var Collection<int, int>|null $firstChunk */
        $firstChunk = $chunks->first();
        self::assertNotNull($firstChunk);
        self::assertSame([1, 2], $firstChunk->values()->toArray());
    }

    public function testChunkExactDivision(): void
    {
        $chunks = Collection::make([1, 2, 3, 4])->chunk(2);
        self::assertCount(2, $chunks);
    }

    // ---------------------------------------------------------------- toArray / count / jsonSerialize / getIterator

    public function testCount(): void
    {
        self::assertCount(3, Collection::make([1, 2, 3]));
        self::assertCount(0, Collection::make());
    }

    public function testJsonSerialize(): void
    {
        $c = Collection::make([1, 2, 3]);
        self::assertSame('[1,2,3]', json_encode($c));
    }

    public function testGetIterator(): void
    {
        $c = Collection::make([1, 2, 3]);
        $iter = $c->getIterator();
        self::assertSame(ArrayIterator::class, $iter::class);

        $items = [];
        foreach ($iter as $v) {
            $items[] = $v;
        }

        self::assertSame([1, 2, 3], $items);
    }

    public function testForeachOverCollection(): void
    {
        $items = [];
        foreach (Collection::make(['a', 'b', 'c']) as $v) {
            $items[] = $v;
        }
        self::assertSame(['a', 'b', 'c'], $items);
    }

    // ---------------------------------------------------------------- immutability

    public function testOperationsReturnNewInstance(): void
    {
        $original = Collection::make([1, 2, 3]);
        $mapped = $original->map(fn (int $v): int => $v * 2);

        self::assertNotSame($original, $mapped);
        self::assertSame([1, 2, 3], $original->toArray());
        self::assertSame([2, 4, 6], $mapped->toArray());
    }
}

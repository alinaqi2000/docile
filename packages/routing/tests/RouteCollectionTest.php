<?php

declare(strict_types=1);

namespace Docile\Routing\Tests;

use Docile\Routing\RouteCollection;
use Docile\Routing\RouteDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RouteCollection::class)]
#[CoversClass(RouteDefinition::class)]
final class RouteCollectionTest extends TestCase
{
    private RouteCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new RouteCollection();
    }

    public function testEmptyCollectionHasZeroCount(): void
    {
        self::assertSame(0, $this->collection->count());
        self::assertSame([], $this->collection->all());
    }

    public function testAddIncreasesCount(): void
    {
        $this->collection->add($this->makeRoute('/a'));
        $this->collection->add($this->makeRoute('/b'));

        self::assertSame(2, $this->collection->count());
    }

    public function testAllReturnsAllAddedRoutes(): void
    {
        $r1 = $this->makeRoute('/a');
        $r2 = $this->makeRoute('/b');

        $this->collection->add($r1);
        $this->collection->add($r2);

        self::assertSame([$r1, $r2], $this->collection->all());
    }

    public function testFindByNameReturnsNullWhenNotFound(): void
    {
        self::assertNull($this->collection->findByName('missing'));
    }

    public function testFindByNameReturnsCorrectRoute(): void
    {
        $named = new RouteDefinition(
            methods: ['GET'],
            path: '/users',
            handler: static fn (): string => 'ok',
            name: 'users.index',
        );

        $this->collection->add($named);
        $this->collection->add($this->makeRoute('/other'));

        self::assertSame($named, $this->collection->findByName('users.index'));
    }

    public function testFindByNameIgnoresUnnamedRoutes(): void
    {
        $this->collection->add($this->makeRoute('/unnamed'));

        self::assertNull($this->collection->findByName('unnamed'));
    }

    public function testOverwritingNamedRouteKeepsLastAdded(): void
    {
        $first = new RouteDefinition(methods: ['GET'], path: '/a', handler: fn (): string => 'a', name: 'shared');
        $second = new RouteDefinition(methods: ['POST'], path: '/b', handler: fn (): string => 'b', name: 'shared');

        $this->collection->add($first);
        $this->collection->add($second);

        // Both are in all(), but findByName returns the last one
        self::assertCount(2, $this->collection->all());
        self::assertSame($second, $this->collection->findByName('shared'));
    }

    // ------------------------------------------------------------------ helpers

    private function makeRoute(string $path): RouteDefinition
    {
        return new RouteDefinition(
            methods: ['GET'],
            path: $path,
            handler: static fn (): string => 'ok',
        );
    }
}

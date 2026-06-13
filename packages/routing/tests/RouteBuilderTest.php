<?php

declare(strict_types=1);

namespace Docile\Routing\Tests;

use Docile\Routing\RouteBuilder;
use Docile\Routing\RouteCollection;
use Docile\Routing\RouteDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RouteBuilder::class)]
#[CoversClass(RouteCollection::class)]
#[CoversClass(RouteDefinition::class)]
final class RouteBuilderTest extends TestCase
{
    private RouteCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new RouteCollection();
    }

    public function testGetDefinitionCommitsToCollection(): void
    {
        $builder = new RouteBuilder(
            collection: $this->collection,
            methods: ['GET'],
            path: '/users',
            handler: static fn (): string => 'ok',
        );

        self::assertSame(0, $this->collection->count());

        $definition = $builder->getDefinition();

        self::assertSame(1, $this->collection->count());
        self::assertSame($definition, $this->collection->all()[0]);
    }

    public function testNameSetsRouteName(): void
    {
        $definition = $this->makeBuilder('/users')
            ->name('users.index')
            ->getDefinition();

        self::assertSame('users.index', $definition->name);
    }

    public function testWhereAddsConstraint(): void
    {
        $definition = $this->makeBuilder('/users/{id}')
            ->where('id', '[0-9]+')
            ->getDefinition();

        self::assertSame(['id' => '[0-9]+'], $definition->wheres);
    }

    public function testMultipleWheresAccumulate(): void
    {
        $definition = $this->makeBuilder('/posts/{year}/{slug}')
            ->where('year', '[0-9]{4}')
            ->where('slug', '[a-z0-9-]+')
            ->getDefinition();

        self::assertSame(['year' => '[0-9]{4}', 'slug' => '[a-z0-9-]+'], $definition->wheres);
    }

    public function testMiddlewareAppendsClasses(): void
    {
        $definition = $this->makeBuilder('/admin')
            ->middleware('AuthMiddleware', 'AdminMiddleware')
            ->getDefinition();

        self::assertSame(['AuthMiddleware', 'AdminMiddleware'], $definition->middleware);
    }

    public function testGroupMiddlewareIsMergedFirst(): void
    {
        $builder = new RouteBuilder(
            collection: $this->collection,
            methods: ['GET'],
            path: '/admin/users',
            handler: static fn (): string => 'ok',
            groupMiddleware: ['GroupAuth'],
        );

        $definition = $builder->middleware('RouteCache')->getDefinition();

        self::assertSame(['GroupAuth', 'RouteCache'], $definition->middleware);
    }

    public function testFluentChainingReturnsSameInstance(): void
    {
        $builder = $this->makeBuilder('/test');

        self::assertSame($builder, $builder->name('test'));
        self::assertSame($builder, $builder->where('id', '\d+'));
        self::assertSame($builder, $builder->middleware('SomeMiddleware'));
    }

    public function testMethodsAreStoredOnDefinition(): void
    {
        $builder = new RouteBuilder(
            collection: $this->collection,
            methods: ['POST', 'PUT'],
            path: '/resource',
            handler: static fn (): string => 'ok',
        );

        $definition = $builder->getDefinition();

        self::assertSame(['POST', 'PUT'], $definition->methods);
    }

    // ------------------------------------------------------------------ helpers

    private function makeBuilder(string $path): RouteBuilder
    {
        return new RouteBuilder(
            collection: $this->collection,
            methods: ['GET'],
            path: $path,
            handler: static fn (): string => 'ok',
        );
    }
}

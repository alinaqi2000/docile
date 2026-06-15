<?php

declare(strict_types=1);

namespace Docile\Routing\Tests\Attribute;

use Attribute;
use Docile\Routing\Attribute\Delete;
use Docile\Routing\Attribute\Get;
use Docile\Routing\Attribute\Patch;
use Docile\Routing\Attribute\Post;
use Docile\Routing\Attribute\Put;
use Docile\Routing\Attribute\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Route::class)]
#[CoversClass(Get::class)]
#[CoversClass(Post::class)]
#[CoversClass(Put::class)]
#[CoversClass(Patch::class)]
#[CoversClass(Delete::class)]
final class RouteAttributeTest extends TestCase
{
    // ----------------------------------------------------------------------- Route

    public function testRouteAttributeDefaults(): void
    {
        $attr = new Route('/users');

        self::assertSame('/users', $attr->path);
        self::assertSame(['GET'], $attr->methods);
        self::assertNull($attr->name);
        self::assertSame([], $attr->middleware);
    }

    public function testRouteAttributeFullConstruction(): void
    {
        $attr = new Route(
            path: '/users/{id}',
            methods: ['GET', 'HEAD'],
            name: 'users.show',
            middleware: ['Auth', 'Throttle'],
        );

        self::assertSame('/users/{id}', $attr->path);
        self::assertSame(['GET', 'HEAD'], $attr->methods);
        self::assertSame('users.show', $attr->name);
        self::assertSame(['Auth', 'Throttle'], $attr->middleware);
    }

    public function testRouteAttributeIsRepeatableOnClass(): void
    {
        $ref = new ReflectionClass(Route::class);
        $attrMeta = $ref->getAttributes(Attribute::class);

        self::assertCount(1, $attrMeta);
        $flags = $attrMeta[0]->newInstance()->flags;
        self::assertTrue((bool) ($flags & Attribute::IS_REPEATABLE));
        self::assertTrue((bool) ($flags & Attribute::TARGET_CLASS));
        self::assertTrue((bool) ($flags & Attribute::TARGET_METHOD));
    }

    // ----------------------------------------------------------------------- Get

    public function testGetAttributeDefaults(): void
    {
        $attr = new Get('/articles');

        self::assertSame('/articles', $attr->path);
        self::assertNull($attr->name);
        self::assertSame([], $attr->middleware);
    }

    public function testGetAttributeFullConstruction(): void
    {
        $attr = new Get('/articles/{slug}', 'articles.show', ['CacheMiddleware']);

        self::assertSame('/articles/{slug}', $attr->path);
        self::assertSame('articles.show', $attr->name);
        self::assertSame(['CacheMiddleware'], $attr->middleware);
    }

    public function testGetAttributeIsRepeatable(): void
    {
        $ref = new ReflectionClass(Get::class);
        $attrMeta = $ref->getAttributes(Attribute::class);
        $flags = $attrMeta[0]->newInstance()->flags;

        self::assertTrue((bool) ($flags & Attribute::IS_REPEATABLE));
    }

    // ----------------------------------------------------------------------- Post

    public function testPostAttributeDefaults(): void
    {
        $attr = new Post('/articles');

        self::assertSame('/articles', $attr->path);
        self::assertNull($attr->name);
        self::assertSame([], $attr->middleware);
    }

    public function testPostAttributeFullConstruction(): void
    {
        $attr = new Post('/articles', 'articles.create', ['AuthMiddleware']);

        self::assertSame('/articles', $attr->path);
        self::assertSame('articles.create', $attr->name);
        self::assertSame(['AuthMiddleware'], $attr->middleware);
    }

    // ----------------------------------------------------------------------- Put

    public function testPutAttributeDefaults(): void
    {
        $attr = new Put('/articles/{id}');

        self::assertSame('/articles/{id}', $attr->path);
        self::assertNull($attr->name);
        self::assertSame([], $attr->middleware);
    }

    public function testPutAttributeFullConstruction(): void
    {
        $attr = new Put('/articles/{id}', 'articles.update', ['AuthMiddleware']);

        self::assertSame('articles.update', $attr->name);
    }

    // ----------------------------------------------------------------------- Patch

    public function testPatchAttributeDefaults(): void
    {
        $attr = new Patch('/articles/{id}');

        self::assertSame('/articles/{id}', $attr->path);
        self::assertNull($attr->name);
    }

    public function testPatchAttributeFullConstruction(): void
    {
        $attr = new Patch('/articles/{id}', 'articles.patch', ['AuthMiddleware']);

        self::assertSame('articles.patch', $attr->name);
        self::assertSame(['AuthMiddleware'], $attr->middleware);
    }

    // ----------------------------------------------------------------------- Delete

    public function testDeleteAttributeDefaults(): void
    {
        $attr = new Delete('/articles/{id}');

        self::assertSame('/articles/{id}', $attr->path);
        self::assertNull($attr->name);
        self::assertSame([], $attr->middleware);
    }

    public function testDeleteAttributeFullConstruction(): void
    {
        $attr = new Delete('/articles/{id}', 'articles.destroy', ['AuthMiddleware']);

        self::assertSame('articles.destroy', $attr->name);
        self::assertSame(['AuthMiddleware'], $attr->middleware);
    }
}

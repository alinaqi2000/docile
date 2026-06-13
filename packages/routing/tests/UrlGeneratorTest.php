<?php

declare(strict_types=1);

namespace Docile\Routing\Tests;

use Docile\Routing\Exception\RouteNotFoundException;
use Docile\Routing\RouteCollection;
use Docile\Routing\RouteDefinition;
use Docile\Routing\UrlGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlGenerator::class)]
#[CoversClass(RouteCollection::class)]
#[CoversClass(RouteDefinition::class)]
#[CoversClass(RouteNotFoundException::class)]
final class UrlGeneratorTest extends TestCase
{
    private RouteCollection $collection;
    private UrlGenerator $generator;

    protected function setUp(): void
    {
        $this->collection = new RouteCollection();
        $this->generator = new UrlGenerator($this->collection);
    }

    // ----------------------------------------------------------------------- basic

    public function testStaticRouteGeneratesCorrectUrl(): void
    {
        $this->addNamed('/users', 'users.index');

        self::assertSame('/users', $this->generator->route('users.index'));
    }

    public function testDynamicRouteSubstitutesParams(): void
    {
        $this->addNamed('/users/{id}', 'users.show');

        self::assertSame('/users/42', $this->generator->route('users.show', ['id' => '42']));
    }

    public function testMultipleParamsAreSubstituted(): void
    {
        $this->addNamed('/posts/{year}/{slug}', 'posts.show');

        $url = $this->generator->route('posts.show', ['year' => '2024', 'slug' => 'hello-world']);

        self::assertSame('/posts/2024/hello-world', $url);
    }

    public function testExtraParamsBecomQueryString(): void
    {
        $this->addNamed('/search', 'search');

        $url = $this->generator->route('search', ['q' => 'php', 'page' => '2']);

        self::assertSame('/search?q=php&page=2', $url);
    }

    public function testMixedParamsPathAndQuery(): void
    {
        $this->addNamed('/users/{id}/posts', 'users.posts');

        $url = $this->generator->route('users.posts', ['id' => '5', 'sort' => 'asc']);

        self::assertStringContainsString('/users/5/posts', $url);
        self::assertStringContainsString('sort=asc', $url);
    }

    // ----------------------------------------------------------------------- absolute

    public function testAbsoluteUrlPrependBaseUrl(): void
    {
        $this->addNamed('/users', 'users.index');

        $url = $this->generator->route(
            name: 'users.index',
            params: [],
            absolute: true,
            baseUrl: 'https://example.com',
        );

        self::assertSame('https://example.com/users', $url);
    }

    public function testAbsoluteUrlStripsTrailingSlashFromBase(): void
    {
        $this->addNamed('/users', 'users.index');

        $url = $this->generator->route(
            name: 'users.index',
            params: [],
            absolute: true,
            baseUrl: 'https://example.com/',
        );

        self::assertSame('https://example.com/users', $url);
    }

    public function testAbsoluteUrlWithParams(): void
    {
        $this->addNamed('/users/{id}', 'users.show');

        $url = $this->generator->route(
            name: 'users.show',
            params: ['id' => '7'],
            absolute: true,
            baseUrl: 'https://example.com',
        );

        self::assertSame('https://example.com/users/7', $url);
    }

    // ----------------------------------------------------------------------- errors

    public function testUnknownRouteNameThrowsRouteNotFoundException(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('"missing"');

        $this->generator->route('missing');
    }

    // ------------------------------------------------------------------ helpers

    private function addNamed(string $path, string $name): void
    {
        $this->collection->add(new RouteDefinition(
            methods: ['GET'],
            path: $path,
            handler: static fn (): string => 'ok',
            name: $name,
        ));
    }
}

<?php

declare(strict_types=1);

namespace Docile\Routing\Tests;

use Docile\Routing\Exception\MethodNotAllowedRoutingException;
use Docile\Routing\Exception\NotFoundRoutingException;
use Docile\Routing\Matcher;
use Docile\Routing\MatchedRoute;
use Docile\Routing\RouteCollection;
use Docile\Routing\RouteCompiler;
use Docile\Routing\RouteDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matcher::class)]
#[CoversClass(MatchedRoute::class)]
#[CoversClass(RouteCompiler::class)]
#[CoversClass(RouteCollection::class)]
#[CoversClass(RouteDefinition::class)]
#[CoversClass(NotFoundRoutingException::class)]
#[CoversClass(MethodNotAllowedRoutingException::class)]
final class MatcherTest extends TestCase
{
    private RouteCollection $collection;
    private Matcher $matcher;

    protected function setUp(): void
    {
        $this->collection = new RouteCollection();
        $this->matcher = new Matcher($this->collection);
    }

    // ----------------------------------------------------------------------- static routes

    public function testStaticRouteMatchesExactPath(): void
    {
        $this->addRoute(['GET'], '/users');

        $result = $this->matcher->match('GET', '/users');

        self::assertSame('/users', $result->definition->path);
        self::assertSame([], $result->params);
    }

    public function testStaticRouteMatchIsCaseInsensitiveForMethod(): void
    {
        $this->addRoute(['GET'], '/ping');

        $result = $this->matcher->match('get', '/ping');

        self::assertSame('/ping', $result->definition->path);
    }

    public function testStaticRouteNotFoundThrows(): void
    {
        $this->expectException(NotFoundRoutingException::class);

        $this->matcher->match('GET', '/missing');
    }

    public function testStaticRouteWrongMethodThrowsMethodNotAllowed(): void
    {
        $this->addRoute(['GET'], '/users');

        $e = null;

        try {
            $this->matcher->match('POST', '/users');
        } catch (MethodNotAllowedRoutingException $ex) {
            $e = $ex;
        }

        self::assertNotNull($e);
        self::assertSame(['GET'], $e->getAllowedMethods());
    }

    public function testStaticRouteMultipleMethodsAllowedReturned(): void
    {
        $this->addRoute(['GET', 'HEAD'], '/users');

        $e = null;

        try {
            $this->matcher->match('DELETE', '/users');
        } catch (MethodNotAllowedRoutingException $ex) {
            $e = $ex;
        }

        self::assertNotNull($e);
        self::assertContains('GET', $e->getAllowedMethods());
        self::assertContains('HEAD', $e->getAllowedMethods());
    }

    public function testMultipleStaticRoutesOnSamePath(): void
    {
        $this->addRoute(['GET'], '/resource');
        $this->addRoute(['POST'], '/resource');

        $getResult = $this->matcher->match('GET', '/resource');
        $postResult = $this->matcher->match('POST', '/resource');

        self::assertSame(['GET'], $getResult->definition->methods);
        self::assertSame(['POST'], $postResult->definition->methods);
    }

    // ----------------------------------------------------------------------- dynamic routes

    public function testDynamicRouteSingleParam(): void
    {
        $this->addRoute(['GET'], '/users/{id}');

        $result = $this->matcher->match('GET', '/users/42');

        self::assertSame(['id' => '42'], $result->params);
    }

    public function testDynamicRouteMultipleParams(): void
    {
        $this->addRoute(['GET'], '/posts/{year}/{slug}');

        $result = $this->matcher->match('GET', '/posts/2024/hello-world');

        self::assertSame(['year' => '2024', 'slug' => 'hello-world'], $result->params);
    }

    public function testDynamicRouteWrongMethodThrowsMethodNotAllowed(): void
    {
        $this->addRoute(['GET'], '/users/{id}');

        $e = null;

        try {
            $this->matcher->match('DELETE', '/users/5');
        } catch (MethodNotAllowedRoutingException $ex) {
            $e = $ex;
        }

        self::assertNotNull($e);
        self::assertSame(['GET'], $e->getAllowedMethods());
    }

    public function testDynamicRouteNoPathMatchThrowsNotFound(): void
    {
        $this->addRoute(['GET'], '/users/{id}');

        $this->expectException(NotFoundRoutingException::class);

        $this->matcher->match('GET', '/missing/path');
    }

    public function testDynamicRouteWithWhereConstraintMatchesValid(): void
    {
        $definition = new RouteDefinition(
            methods: ['GET'],
            path: '/items/{id}',
            handler: static fn (): string => 'ok',
            wheres: ['id' => '[0-9]+'],
        );

        $this->collection->add($definition);

        $result = $this->matcher->match('GET', '/items/99');

        self::assertSame(['id' => '99'], $result->params);
    }

    public function testDynamicRouteWithWhereConstraintRejectsInvalid(): void
    {
        $definition = new RouteDefinition(
            methods: ['GET'],
            path: '/items/{id}',
            handler: static fn (): string => 'ok',
            wheres: ['id' => '[0-9]+'],
        );

        $this->collection->add($definition);

        $this->expectException(NotFoundRoutingException::class);

        $this->matcher->match('GET', '/items/abc');
    }

    public function testMatchedRouteCarriesCorrectDefinition(): void
    {
        $definition = new RouteDefinition(
            methods: ['POST'],
            path: '/submit',
            handler: static fn (): string => 'submitted',
            name: 'form.submit',
        );

        $this->collection->add($definition);

        $result = $this->matcher->match('POST', '/submit');

        self::assertSame($definition, $result->definition);
        self::assertSame('form.submit', $result->definition->name);
    }

    public function testEmptyCollectionThrowsNotFound(): void
    {
        $this->expectException(NotFoundRoutingException::class);

        $this->matcher->match('GET', '/anything');
    }

    // ------------------------------------------------------------------ helpers

    /**
     * @param array<string> $methods
     */
    private function addRoute(array $methods, string $path): void
    {
        $this->collection->add(new RouteDefinition(
            methods: $methods,
            path: $path,
            handler: static fn (): string => 'ok',
        ));
    }
}

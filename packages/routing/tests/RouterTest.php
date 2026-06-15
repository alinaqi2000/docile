<?php

declare(strict_types=1);

namespace Docile\Routing\Tests;

use Closure;
use Docile\Routing\Exception\MethodNotAllowedRoutingException;
use Docile\Routing\Exception\NotFoundRoutingException;
use Docile\Routing\Matcher;
use Docile\Routing\MatchedRoute;
use Docile\Routing\RouteBuilder;
use Docile\Routing\RouteCollection;
use Docile\Routing\RouteCompiler;
use Docile\Routing\RouteDefinition;
use Docile\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

#[CoversClass(Router::class)]
#[CoversClass(RouteBuilder::class)]
#[CoversClass(RouteCollection::class)]
#[CoversClass(RouteDefinition::class)]
#[CoversClass(Matcher::class)]
#[CoversClass(RouteCompiler::class)]
#[CoversClass(MatchedRoute::class)]
#[CoversClass(NotFoundRoutingException::class)]
#[CoversClass(MethodNotAllowedRoutingException::class)]
final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    // ----------------------------------------------------------------------- fluent registration

    public function testGetRegistersRoute(): void
    {
        $builder = $this->router->get('/users', static fn (): string => 'ok');
        $definition = $builder->getDefinition();

        self::assertSame(['GET'], $definition->methods);
        self::assertSame('/users', $definition->path);
    }

    public function testPostRegistersRoute(): void
    {
        $definition = $this->router->post('/users', static fn (): string => 'ok')->getDefinition();

        self::assertSame(['POST'], $definition->methods);
    }

    public function testPutRegistersRoute(): void
    {
        $definition = $this->router->put('/users/{id}', static fn (): string => 'ok')->getDefinition();

        self::assertSame(['PUT'], $definition->methods);
    }

    public function testPatchRegistersRoute(): void
    {
        $definition = $this->router->patch('/users/{id}', static fn (): string => 'ok')->getDefinition();

        self::assertSame(['PATCH'], $definition->methods);
    }

    public function testDeleteRegistersRoute(): void
    {
        $definition = $this->router->delete('/users/{id}', static fn (): string => 'ok')->getDefinition();

        self::assertSame(['DELETE'], $definition->methods);
    }

    public function testAnyRegistersMultipleMethods(): void
    {
        $definition = $this->router->any('/ping', static fn (): string => 'pong')->getDefinition();

        self::assertContains('GET', $definition->methods);
        self::assertContains('POST', $definition->methods);
    }

    public function testMatchRegistersSpecifiedMethods(): void
    {
        $definition = $this->router->match(['get', 'post'], '/resource', static fn (): string => 'ok')->getDefinition();

        // Methods should be uppercased
        self::assertSame(['GET', 'POST'], $definition->methods);
    }

    // ----------------------------------------------------------------------- route builder fluency

    public function testRouteBuilderNamePropagates(): void
    {
        $definition = $this->router->get('/users', static fn (): string => 'ok')
            ->name('users.index')
            ->getDefinition();

        self::assertSame('users.index', $definition->name);
    }

    public function testRouteBuilderWherePropagates(): void
    {
        $definition = $this->router->get('/users/{id}', static fn (): string => 'ok')
            ->where('id', '[0-9]+')
            ->getDefinition();

        self::assertSame(['id' => '[0-9]+'], $definition->wheres);
    }

    public function testRouteBuilderMiddlewarePropagates(): void
    {
        $definition = $this->router->get('/admin', static fn (): string => 'ok')
            ->middleware('AuthMiddleware')
            ->getDefinition();

        self::assertSame(['AuthMiddleware'], $definition->middleware);
    }

    // ----------------------------------------------------------------------- groups

    public function testGroupPrefixesChildRoutes(): void
    {
        $this->router->group('/api', [], function (Router $r): void {
            $r->get('/users', static fn (): string => 'ok')->getDefinition();
        });

        $collection = $this->router->getCollection();
        $routes = $collection->all();

        self::assertCount(1, $routes);
        self::assertSame('/api/users', $routes[0]->path);
    }

    public function testGroupMiddlewareIsInheritedByChildren(): void
    {
        $this->router->group('/api', ['AuthMiddleware'], function (Router $r): void {
            $r->get('/secret', static fn (): string => 'ok')->getDefinition();
        });

        $routes = $this->router->getCollection()->all();

        self::assertContains('AuthMiddleware', $routes[0]->middleware);
    }

    public function testNestedGroupsCombinePrefixes(): void
    {
        $this->router->group('/api', [], function (Router $r): void {
            $r->group('/v1', [], function (Router $r): void {
                $r->get('/users', static fn (): string => 'ok')->getDefinition();
            });
        });

        $routes = $this->router->getCollection()->all();

        self::assertSame('/api/v1/users', $routes[0]->path);
    }

    public function testNestedGroupsMergeMiddleware(): void
    {
        $this->router->group('/api', ['Auth'], function (Router $r): void {
            $r->group('/admin', ['Admin'], function (Router $r): void {
                $r->get('/dashboard', static fn (): string => 'ok')->getDefinition();
            });
        });

        $routes = $this->router->getCollection()->all();

        self::assertContains('Auth', $routes[0]->middleware);
        self::assertContains('Admin', $routes[0]->middleware);
    }

    public function testGroupContextRestoredAfterCallback(): void
    {
        $this->router->group('/api', ['AuthMiddleware'], function (Router $r): void {
            $r->get('/inside', static fn (): string => 'ok')->getDefinition();
        });

        // Route registered outside group should not have the group prefix/middleware
        $this->router->get('/outside', static fn (): string => 'ok')->getDefinition();

        $routes = $this->router->getCollection()->all();

        self::assertSame('/api/inside', $routes[0]->path);
        self::assertContains('AuthMiddleware', $routes[0]->middleware);

        self::assertSame('/outside', $routes[1]->path);
        self::assertSame([], $routes[1]->middleware);
    }

    // ----------------------------------------------------------------------- dispatch

    public function testDispatchMatchesStaticRoute(): void
    {
        $this->router->get('/users', static fn (): string => 'ok')->getDefinition();

        $request = $this->makeRequest('GET', '/users');
        $result = $this->router->dispatch($request);

        self::assertSame('/users', $result->definition->path);
    }

    public function testDispatchMatchesDynamicRoute(): void
    {
        $this->router->get('/users/{id}', static fn (): string => 'ok')->getDefinition();

        $request = $this->makeRequest('GET', '/users/123');
        $result = $this->router->dispatch($request);

        self::assertSame(['id' => '123'], $result->params);
    }

    public function testDispatchThrowsNotFoundForMissingRoute(): void
    {
        $this->expectException(NotFoundRoutingException::class);

        $this->router->dispatch($this->makeRequest('GET', '/missing'));
    }

    public function testDispatchThrowsMethodNotAllowed(): void
    {
        $this->router->get('/users', static fn (): string => 'ok')->getDefinition();

        $this->expectException(MethodNotAllowedRoutingException::class);

        $this->router->dispatch($this->makeRequest('DELETE', '/users'));
    }

    // ----------------------------------------------------------------------- collection

    public function testGetCollectionReturnsCollection(): void
    {
        $collection = $this->router->getCollection();

        self::assertSame(0, $collection->count()); // confirms it's a usable RouteCollection instance
    }

    public function testCustomCollectionCanBeInjected(): void
    {
        $collection = new RouteCollection();
        $router = new Router($collection);

        self::assertSame($collection, $router->getCollection());
    }

    // ----------------------------------------------------------------------- path normalisation

    public function testRootPathNormalisedToSlash(): void
    {
        $definition = $this->router->get('/', static fn (): string => 'root')->getDefinition();

        self::assertSame('/', $definition->path);
    }

    public function testLeadingSlashAddedIfMissing(): void
    {
        $definition = $this->router->get('users', static fn (): string => 'ok')->getDefinition();

        self::assertSame('/users', $definition->path);
    }

    // ------------------------------------------------------------------ helpers

    private function makeRequest(string $method, string $path): ServerRequestInterface
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn($path);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn($method);
        $request->method('getUri')->willReturn($uri);

        return $request;
    }
}

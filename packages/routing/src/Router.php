<?php

declare(strict_types=1);

namespace Docile\Routing;

use Closure;
use Psr\Http\Message\ServerRequestInterface;

use function array_map;
use function ltrim;
use function rtrim;
use function strtoupper;
use function trim;

/**
 * Fluent route builder and central dispatcher.
 */
final class Router
{
    /** @var array<string> Current group middleware stack */
    private array $groupMiddleware = [];

    /** Current group path prefix */
    private string $groupPrefix = '';

    public function __construct(private readonly RouteCollection $routes = new RouteCollection()) {}

    /**
     * Register a GET route.
     *
     * @param \Closure(mixed...): mixed|array{0: class-string, 1: string}|class-string $handler
     */
    public function get(string $path, mixed $handler): RouteBuilder
    {
        return $this->addRoute(['GET'], $path, $handler);
    }

    /**
     * Register a POST route.
     *
     * @param \Closure(mixed...): mixed|array{0: class-string, 1: string}|class-string $handler
     */
    public function post(string $path, mixed $handler): RouteBuilder
    {
        return $this->addRoute(['POST'], $path, $handler);
    }

    /**
     * Register a PUT route.
     *
     * @param \Closure(mixed...): mixed|array{0: class-string, 1: string}|class-string $handler
     */
    public function put(string $path, mixed $handler): RouteBuilder
    {
        return $this->addRoute(['PUT'], $path, $handler);
    }

    /**
     * Register a PATCH route.
     *
     * @param \Closure(mixed...): mixed|array{0: class-string, 1: string}|class-string $handler
     */
    public function patch(string $path, mixed $handler): RouteBuilder
    {
        return $this->addRoute(['PATCH'], $path, $handler);
    }

    /**
     * Register a DELETE route.
     *
     * @param \Closure(mixed...): mixed|array{0: class-string, 1: string}|class-string $handler
     */
    public function delete(string $path, mixed $handler): RouteBuilder
    {
        return $this->addRoute(['DELETE'], $path, $handler);
    }

    /**
     * Register a route matching any HTTP method.
     *
     * @param \Closure(mixed...): mixed|array{0: class-string, 1: string}|class-string $handler
     */
    public function any(string $path, mixed $handler): RouteBuilder
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], $path, $handler);
    }

    /**
     * Register a route matching the specified HTTP methods.
     *
     * @param array<string> $methods
     * @param \Closure(mixed...): mixed|array{0: class-string, 1: string}|class-string $handler
     */
    public function match(array $methods, string $path, mixed $handler): RouteBuilder
    {
        $uppercased = array_map(strtoupper(...), $methods);

        return $this->addRoute($uppercased, $path, $handler);
    }

    /**
     * Group routes under a shared prefix and/or middleware set.
     *
     * @param array<string> $middleware
     * @param Closure(static): void $callback
     */
    public function group(string $prefix, array $middleware, Closure $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $previousPrefix . '/' . trim($prefix, '/');
        $this->groupMiddleware = [...$previousMiddleware, ...$middleware];

        $callback($this);

        // Restore previous group context
        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    /**
     * Dispatch the request and return the matched route.
     */
    public function dispatch(ServerRequestInterface $request): MatchedRoute
    {
        $matcher = new Matcher($this->routes);

        return $matcher->match(
            $request->getMethod(),
            $request->getUri()->getPath(),
        );
    }

    /**
     * Access the underlying route collection.
     */
    public function getCollection(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Internal helper: normalise the path with the current group prefix and
     * create a RouteBuilder backed by the collection.
     *
     * @param array<string> $methods
     * @param \Closure(mixed...): mixed|array{0: class-string, 1: string}|class-string $handler
     */
    private function addRoute(array $methods, string $path, mixed $handler): RouteBuilder
    {
        $fullPath = $this->normalisePath($this->groupPrefix . '/' . ltrim($path, '/'));

        $builder = new RouteBuilder(
            collection: $this->routes,
            methods: $methods,
            path: $fullPath,
            handler: $handler,
            groupMiddleware: $this->groupMiddleware,
        );

        // Auto-commit the route to the collection
        $builder->getDefinition();

        return $builder;
    }

    /**
     * Ensure the path starts with a single slash and has no trailing slash
     * (except for the root '/').
     */
    private function normalisePath(string $path): string
    {
        $normalised = '/' . trim($path, '/');

        return $normalised === '/' ? '/' : rtrim($normalised, '/');
    }
}

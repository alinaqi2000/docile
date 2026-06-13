<?php

declare(strict_types=1);

namespace Docile\Routing;

/**
 * Mutable collection of RouteDefinition instances.
 */
final class RouteCollection
{
    /** @var array<int, RouteDefinition> */
    private array $routes = [];

    /** @var array<string, RouteDefinition> */
    private array $namedRoutes = [];

    /**
     * Add a route definition to the collection.
     */
    public function add(RouteDefinition $route): void
    {
        $this->routes[] = $route;

        if ($route->name !== null) {
            $this->namedRoutes[$route->name] = $route;
        }
    }

    /**
     * Return all registered route definitions.
     *
     * @return array<int, RouteDefinition>
     */
    public function all(): array
    {
        return $this->routes;
    }

    /**
     * Find a route by its name.
     */
    public function findByName(string $name): ?RouteDefinition
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Return the number of registered routes.
     */
    public function count(): int
    {
        return count($this->routes);
    }
}

<?php

declare(strict_types=1);

namespace Docile\Routing;

/**
 * Fluent builder returned by Router registration methods.
 * Commits the final RouteDefinition to the collection on demand.
 */
final class RouteBuilder
{
    private ?string $name = null;

    /** @var array<string, string> */
    private array $wheres = [];

    /** @var array<string> */
    private array $middleware = [];

    /**
     * @param array<string> $methods
     * @param array<string> $groupMiddleware
     * @param \Closure(mixed...): mixed|array{0: class-string, 1: string}|class-string $handler
     */
    public function __construct(
        private readonly RouteCollection $collection,
        private readonly array $methods,
        private readonly string $path,
        private readonly mixed $handler,
        array $groupMiddleware = [],
    ) {
        $this->middleware = $groupMiddleware;
    }

    /**
     * Set a name for the route.
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Add a regex constraint for a named parameter.
     */
    public function where(string $param, string $regex): static
    {
        $this->wheres[$param] = $regex;

        return $this;
    }

    /**
     * Append middleware class-names to the route.
     */
    public function middleware(string ...$middleware): static
    {
        foreach ($middleware as $m) {
            $this->middleware[] = $m;
        }

        return $this;
    }

    /**
     * Build and commit the RouteDefinition to the collection, then return it.
     */
    public function getDefinition(): RouteDefinition
    {
        $definition = new RouteDefinition(
            methods: $this->methods,
            path: $this->path,
            handler: $this->handler,
            name: $this->name,
            middleware: $this->middleware,
            wheres: $this->wheres,
        );

        $this->collection->add($definition);

        return $definition;
    }
}

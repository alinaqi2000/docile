<?php

declare(strict_types=1);

namespace Docile\Routing;

use Docile\Routing\Exception\MethodNotAllowedRoutingException;
use Docile\Routing\Exception\NotFoundRoutingException;

use function array_key_exists;
use function array_unique;
use function array_values;
use function implode;
use function in_array;
use function preg_match;
use function sprintf;
use function strtoupper;

/**
 * Matches an incoming HTTP method + path against the compiled route collection.
 *
 * Strategy:
 *   1. Separate static from dynamic routes; check static map first (O(1)).
 *   2. For dynamic routes iterate compiled regexes.
 *   3. If a path matches but the method does not → MethodNotAllowedRoutingException.
 *   4. No path match at all → NotFoundRoutingException.
 */
final class Matcher
{
    /**
     * Pre-built static route map: path → list of CompiledRoute.
     *
     * @var array<string, array<int, CompiledRoute>>
     */
    private array $staticMap = [];

    /**
     * Dynamic (parameterised) compiled routes.
     *
     * @var array<int, CompiledRoute>
     */
    private array $dynamicRoutes = [];

    private bool $compiled = false;

    public function __construct(private readonly RouteCollection $collection) {}

    /**
     * Match the given HTTP method and path against registered routes.
     *
     * @throws NotFoundRoutingException          When no route path matches.
     * @throws MethodNotAllowedRoutingException  When the path matches but the method is not allowed.
     */
    public function match(string $method, string $path): MatchedRoute
    {
        $this->ensureCompiled();

        $method = strtoupper($method);

        // --- 1. Static lookup (O(1)) ---
        if (array_key_exists($path, $this->staticMap)) {
            $allowedMethods = [];

            foreach ($this->staticMap[$path] as $compiled) {
                $allowedMethods = [...$allowedMethods, ...$compiled->definition->methods];

                if (in_array($method, $compiled->definition->methods, true)) {
                    return new MatchedRoute(
                        definition: $compiled->definition,
                        params: [],
                    );
                }
            }

            throw new MethodNotAllowedRoutingException(
                array_values(array_unique($allowedMethods)),
            );
        }

        // --- 2. Dynamic lookup ---
        /** @var array<string> $allowedMethodsForPath */
        $allowedMethodsForPath = [];

        foreach ($this->dynamicRoutes as $compiled) {
            if (preg_match($compiled->regex, $path, $matches) !== 1) {
                continue;
            }

            // Path matched — collect allowed methods
            $allowedMethodsForPath = [...$allowedMethodsForPath, ...$compiled->definition->methods];

            if (!in_array($method, $compiled->definition->methods, true)) {
                continue;
            }

            // Extract named captures only (skip numeric keys)
            /** @var array<string, string> $params */
            $params = [];

            foreach ($compiled->paramNames as $paramName) {
                if (isset($matches[$paramName]) && $matches[$paramName] !== '') {
                    $params[$paramName] = $matches[$paramName];
                }
            }

            return new MatchedRoute(
                definition: $compiled->definition,
                params: $params,
            );
        }

        if ($allowedMethodsForPath !== []) {
            throw new MethodNotAllowedRoutingException(
                array_values(array_unique($allowedMethodsForPath)),
            );
        }

        throw new NotFoundRoutingException(
            sprintf('No route found for "%s %s".', $method, $path),
        );
    }

    /**
     * Compile all routes from the collection and categorise them.
     */
    private function ensureCompiled(): void
    {
        if ($this->compiled) {
            return;
        }

        $this->staticMap = [];
        $this->dynamicRoutes = [];

        $compiler = new RouteCompiler();

        foreach ($this->collection->all() as $definition) {
            $compiled = $compiler->compile($definition);

            if ($compiled->paramNames === []) {
                $this->staticMap[$definition->path][] = $compiled;
            } else {
                $this->dynamicRoutes[] = $compiled;
            }
        }

        $this->compiled = true;
    }
}

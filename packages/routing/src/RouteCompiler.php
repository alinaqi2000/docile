<?php

declare(strict_types=1);

namespace Docile\Routing;

use Docile\Routing\Exception\InvalidRouteException;

use function preg_match_all;
use function preg_quote;
use function preg_replace_callback;
use function sprintf;

/**
 * Compiles a RouteDefinition into a CompiledRoute with a named-capture regex.
 */
final class RouteCompiler
{
    private const string DEFAULT_PARAM_PATTERN = '[^/]+';

    /**
     * Compile a route definition into a regex-based CompiledRoute.
     *
     * - `{id}` becomes `(?P<id>[^/]+)` unless a `where` constraint overrides it.
     * - Static routes (no parameters) get an anchored literal regex.
     *
     * @throws InvalidRouteException When the route path is malformed.
     */
    public function compile(RouteDefinition $route): CompiledRoute
    {
        $path = $route->path;

        // Collect parameter names in order
        /** @var array<int, list<non-empty-string>> $matches */
        $matches = [];
        preg_match_all('/\{(\w+)\}/', $path, $matches);

        /** @var list<non-empty-string> $paramNames */
        $paramNames = $matches[1];

        // Static route — no parameters
        if ($paramNames === []) {
            $regex = '#^' . preg_quote($path, '#') . '$#';

            return new CompiledRoute(
                definition: $route,
                regex: $regex,
                paramNames: [],
            );
        }

        // Dynamic route — replace {param} with named capture groups
        $regex = preg_replace_callback(
            '/\{(\w+)\}/',
            static function (array $m) use ($route): string {
                /** @var array{0: non-empty-string, 1: non-empty-string} $m */
                $param = $m[1];
                $pattern = $route->wheres[$param] ?? self::DEFAULT_PARAM_PATTERN;

                return sprintf('(?P<%s>%s)', $param, $pattern);
            },
            $path,
        );

        if ($regex === null) {
            throw new InvalidRouteException(
                sprintf('Failed to compile route path: %s', $path),
            );
        }

        $regex = '#^' . $regex . '$#';

        return new CompiledRoute(
            definition: $route,
            regex: $regex,
            paramNames: $paramNames,
        );
    }
}

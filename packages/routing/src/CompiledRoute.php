<?php

declare(strict_types=1);

namespace Docile\Routing;

/**
 * Immutable value object representing a route compiled into a regex.
 */
final readonly class CompiledRoute
{
    /**
     * @param RouteDefinition $definition  The original route definition
     * @param string          $regex       Compiled regex with named captures
     * @param array<string>   $paramNames  Ordered parameter names extracted from the path
     */
    public function __construct(
        public readonly RouteDefinition $definition,
        public readonly string $regex,
        public readonly array $paramNames,
    ) {}
}

<?php

declare(strict_types=1);

namespace Docile\Routing;

/**
 * Immutable value object representing the result of a successful route match.
 */
final readonly class MatchedRoute
{
    /**
     * @param RouteDefinition       $definition  The matched route definition
     * @param array<string, string> $params      Extracted URL parameters
     */
    public function __construct(
        public readonly RouteDefinition $definition,
        public readonly array $params,
    ) {}
}

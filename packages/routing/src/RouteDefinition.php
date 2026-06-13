<?php

declare(strict_types=1);

namespace Docile\Routing;

/**
 * Immutable value object representing a registered route before compilation.
 */
final readonly class RouteDefinition
{
    /**
     * @param array<string>         $methods    HTTP methods (uppercase)
     * @param string                $path       e.g. '/users/{id}'
     * @param \Closure(mixed...): mixed|array{0: class-string, 1: string}|class-string $handler
     * @param string|null           $name
     * @param array<string>         $middleware  Middleware class-names
     * @param array<string, string> $wheres      param => regex constraints
     */
    public function __construct(
        public readonly array $methods,
        public readonly string $path,
        public readonly mixed $handler,
        public readonly ?string $name = null,
        public readonly array $middleware = [],
        public readonly array $wheres = [],
    ) {}
}

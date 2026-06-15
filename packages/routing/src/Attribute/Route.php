<?php

declare(strict_types=1);

namespace Docile\Routing\Attribute;

use Attribute;

/**
 * Generic route attribute — can be stacked on classes and methods.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Route
{
    /**
     * @param array<string> $methods
     * @param array<string> $middleware
     */
    public function __construct(
        public readonly string $path,
        public readonly array $methods = ['GET'],
        public readonly ?string $name = null,
        public readonly array $middleware = [],
    ) {}
}

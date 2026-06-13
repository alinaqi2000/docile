<?php

declare(strict_types=1);

namespace Docile\Routing\Attribute;

use Attribute;

/**
 * Shortcut attribute for DELETE routes.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Delete
{
    /**
     * @param array<string> $middleware
     */
    public function __construct(
        public readonly string $path,
        public readonly ?string $name = null,
        public readonly array $middleware = [],
    ) {}
}

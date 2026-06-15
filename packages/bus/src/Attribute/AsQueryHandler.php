<?php

declare(strict_types=1);

namespace Docile\Bus\Attribute;

use Attribute;

/**
 * Marks a class as a query handler.
 *
 * The handled query class is inferred from the first constructor parameter type
 * unless explicitly specified via the $handles argument.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsQueryHandler
{
    /**
     * @param class-string|null $handles The query class this handler handles, or null to infer from the constructor.
     */
    public function __construct(public ?string $handles = null) {}
}

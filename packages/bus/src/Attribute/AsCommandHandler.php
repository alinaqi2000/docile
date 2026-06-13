<?php

declare(strict_types=1);

namespace Docile\Bus\Attribute;

use Attribute;

/**
 * Marks a class as a command handler.
 *
 * The handled command class is inferred from the first constructor parameter type
 * unless explicitly specified via the $handles argument.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsCommandHandler
{
    /**
     * @param class-string|null $handles The command class this handler handles, or null to infer from the constructor.
     */
    public function __construct(public ?string $handles = null) {}
}

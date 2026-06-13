<?php

declare(strict_types=1);

namespace Docile\Validation\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class InList
{
    /** @param array<mixed> $choices */
    public function __construct(
        public readonly array $choices,
        public readonly string $message = 'Value not in allowed list.',
    ) {}
}
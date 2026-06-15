<?php

declare(strict_types=1);

namespace Docile\Validation\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Range
{
    public function __construct(
        public readonly int|float|null $min = null,
        public readonly int|float|null $max = null,
        public readonly string $message = 'Value out of range.',
    ) {}
}
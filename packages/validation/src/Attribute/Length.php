<?php

declare(strict_types=1);

namespace Docile\Validation\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Length
{
    public function __construct(
        public readonly ?int $min = null,
        public readonly ?int $max = null,
        public readonly string $message = 'Length out of range.',
    ) {}
}
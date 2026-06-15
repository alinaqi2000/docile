<?php

declare(strict_types=1);

namespace Docile\Validation\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Regex
{
    public function __construct(
        public readonly string $pattern,
        public readonly string $message = 'Value does not match pattern.',
    ) {}
}
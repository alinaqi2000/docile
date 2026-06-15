<?php

declare(strict_types=1);

namespace Docile\Console\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class AsCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $description = '',
    ) {}
}
<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

final class HasPrimitiveDefault
{
    public function __construct(
        public readonly string $name = 'default',
        public readonly int $count = 3,
    ) {}
}

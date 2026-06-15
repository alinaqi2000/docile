<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

final class RequiresPrimitive
{
    public function __construct(public readonly string $name) {}
}

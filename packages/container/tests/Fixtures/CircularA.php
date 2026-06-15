<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

final class CircularA
{
    public function __construct(public readonly CircularB $b) {}
}

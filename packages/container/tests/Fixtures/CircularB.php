<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

final class CircularB
{
    public function __construct(public readonly CircularA $a) {}
}

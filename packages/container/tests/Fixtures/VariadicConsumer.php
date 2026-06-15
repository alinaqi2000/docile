<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

final class VariadicConsumer
{
    /** @var list<int> */
    public readonly array $numbers;

    public function __construct(int ...$numbers)
    {
        $this->numbers = $numbers;
    }
}

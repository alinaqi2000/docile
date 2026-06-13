<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

final class NullLogger implements LoggerInterface
{
    public function channel(): string
    {
        return 'null';
    }
}

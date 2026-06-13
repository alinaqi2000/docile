<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

interface LoggerInterface
{
    public function channel(): string;
}

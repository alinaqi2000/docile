<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

final class FileLogger implements LoggerInterface
{
    public function channel(): string
    {
        return 'file';
    }
}

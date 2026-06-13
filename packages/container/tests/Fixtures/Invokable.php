<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

final class Invokable
{
    public function __invoke(Logger $logger, string $suffix = '!'): string
    {
        return $logger::class . $suffix;
    }
}

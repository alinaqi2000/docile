<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

final class NullableInterfaceDependency
{
    public function __construct(public readonly ?LoggerInterface $logger) {}
}

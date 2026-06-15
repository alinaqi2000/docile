<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

final class ServiceWithDependency
{
    public function __construct(public readonly Logger $logger) {}
}

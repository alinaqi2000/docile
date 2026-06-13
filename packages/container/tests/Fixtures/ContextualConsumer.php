<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

final class ContextualConsumer
{
    public function __construct(public readonly LoggerInterface $logger) {}
}

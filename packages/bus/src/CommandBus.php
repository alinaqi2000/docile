<?php

declare(strict_types=1);

namespace Docile\Bus;

/**
 * Semantic alias for dispatching commands through a MessageBus.
 */
final readonly class CommandBus
{
    public function __construct(private MessageBusInterface $bus) {}

    public function dispatch(object $command): mixed
    {
        return $this->bus->dispatch($command);
    }
}

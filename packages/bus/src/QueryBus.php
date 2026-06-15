<?php

declare(strict_types=1);

namespace Docile\Bus;

/**
 * Semantic alias for dispatching queries through a MessageBus.
 */
final readonly class QueryBus
{
    public function __construct(private MessageBusInterface $bus) {}

    public function ask(object $query): mixed
    {
        return $this->bus->dispatch($query);
    }
}

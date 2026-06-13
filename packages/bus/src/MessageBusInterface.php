<?php

declare(strict_types=1);

namespace Docile\Bus;

/**
 * Dispatches a message (command or query) and returns the handler's result.
 */
interface MessageBusInterface
{
    public function dispatch(object $message): mixed;
}

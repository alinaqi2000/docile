<?php

declare(strict_types=1);

namespace Docile\Bus;

use Docile\Bus\Exception\HandlerNotFoundException;

/**
 * Locates a handler callable for a given message object.
 */
interface HandlerLocatorInterface
{
    /**
     * Returns a callable handler for the given message.
     *
     * @return callable(object): mixed
     *
     * @throws HandlerNotFoundException If no handler is registered for the message.
     */
    public function getHandler(object $message): callable;
}

<?php

declare(strict_types=1);

namespace Docile\Bus;

/**
 * Bus-level middleware that wraps message handling with cross-cutting concerns.
 */
interface MiddlewareInterface
{
    /**
     * Process the message, optionally delegating to the next middleware or handler.
     *
     * @param callable(object): mixed $next
     */
    public function handle(object $message, callable $next): mixed;
}

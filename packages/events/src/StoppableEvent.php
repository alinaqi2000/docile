<?php

declare(strict_types=1);

namespace Docile\Events;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Base class for stoppable domain events.
 *
 * Extend this class and call {@see stopPropagation()} from a listener to
 * prevent subsequent listeners from receiving the event.
 */
abstract class StoppableEvent implements StoppableEventInterface
{
    private bool $stopped = false;

    public function stopPropagation(): void
    {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }
}

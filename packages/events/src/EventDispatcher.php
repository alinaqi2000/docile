<?php

declare(strict_types=1);

namespace Docile\Events;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * PSR-14 event dispatcher.
 *
 * Iterates through all applicable listeners from the {@see ListenerProvider},
 * stopping early if the event implements {@see StoppableEventInterface} and
 * signals that propagation has been stopped.
 */
final class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(private readonly ListenerProvider $provider) {}

    /**
     * Dispatch an event to all registered listeners.
     *
     * Listeners are called in priority order (highest first). If the event
     * implements {@see StoppableEventInterface} and {@see StoppableEventInterface::isPropagationStopped()}
     * returns true, no further listeners will be called.
     *
     * @template T of object
     *
     * @param T $event
     *
     * @return T
     */
    public function dispatch(object $event): object
    {
        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }

        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            $listener($event);
        }

        return $event;
    }
}

<?php

declare(strict_types=1);

namespace Docile\Events;

use Docile\Events\Attribute\AsListener;
use Psr\EventDispatcher\ListenerProviderInterface;
use ReflectionMethod;
use ReflectionObject;

use function array_key_exists;
use function krsort;

/**
 * PSR-14 listener provider with priority ordering and attribute-based subscriber scanning.
 *
 * Listeners are stored as:
 *   array<class-string, array<int, list<callable>>>
 * — keyed by event FQCN, then by priority (higher = first).
 *
 * Within the same priority, listeners are called in insertion order.
 */
final class ListenerProvider implements ListenerProviderInterface
{
    /**
     * @var array<class-string, array<int, list<callable(object): void>>>
     */
    private array $listeners = [];

    /**
     * Register a callable listener for a given event class.
     *
     * @param class-string $eventClass
     * @param callable(object): void $listener
     */
    public function addListener(string $eventClass, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventClass][$priority][] = $listener;
    }

    /**
     * Return all listeners applicable to the given event, ordered by priority descending,
     * then insertion order within the same priority.
     *
     * @return iterable<callable(object): void>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventClass = $event::class;

        if (!array_key_exists($eventClass, $this->listeners)) {
            return;
        }

        $priorityGroups = $this->listeners[$eventClass];
        krsort($priorityGroups);

        foreach ($priorityGroups as $callables) {
            yield from $callables;
        }
    }

    /**
     * Scan all methods on the subscriber for {@see AsListener} attributes and
     * auto-register them as listeners with the declared event class and priority.
     */
    public function subscribe(object $subscriber): void
    {
        $reflection = new ReflectionObject($subscriber);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(AsListener::class);

            foreach ($attributes as $attributeReflection) {
                /** @var AsListener $attribute */
                $attribute = $attributeReflection->newInstance();

                /** @var callable(object): void $listener */
                $listener = [$subscriber, $method->getName()];

                $this->addListener($attribute->event, $listener, $attribute->priority);
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Docile\Events\Attribute;

use Attribute;

/**
 * Marks a method as an event listener for a specific event class.
 *
 * Apply to subscriber methods that should be auto-registered via
 * {@see \Docile\Events\ListenerProvider::subscribe()}.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class AsListener
{
    public function __construct(
        /** @var class-string The fully-qualified class name of the event to listen for. */
        public readonly string $event,
        public readonly int $priority = 0,
    ) {}
}

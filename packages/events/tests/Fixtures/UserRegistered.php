<?php

declare(strict_types=1);

namespace Docile\Events\Tests\Fixtures;

use Docile\Events\StoppableEvent;

/**
 * Stoppable domain event fixture for testing propagation stopping.
 */
final class UserRegistered extends StoppableEvent
{
    public function __construct(
        public readonly string $email,
    ) {}
}

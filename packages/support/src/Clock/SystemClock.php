<?php

declare(strict_types=1);

namespace Docile\Support\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Real-time clock that delegates to the system clock.
 */
final class SystemClock implements ClockInterface
{
    /**
     * Return the current system time as an immutable date-time object.
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}

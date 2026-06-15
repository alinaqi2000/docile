<?php

declare(strict_types=1);

namespace Docile\Support\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Deterministic clock that always returns a fixed point in time.
 *
 * Useful for testing time-sensitive code without needing to rely on
 * the system clock or mocking framework magic.
 */
final class FrozenClock implements ClockInterface
{
    private DateTimeImmutable $frozenAt;

    public function __construct(DateTimeImmutable $frozenAt)
    {
        $this->frozenAt = $frozenAt;
    }

    /**
     * Return the frozen date-time.
     */
    public function now(): DateTimeImmutable
    {
        return $this->frozenAt;
    }

    /**
     * Update the frozen time to a new point.
     *
     * This is intentionally mutable to allow test scenarios to advance time.
     */
    public function setTo(DateTimeImmutable $time): void
    {
        $this->frozenAt = $time;
    }
}

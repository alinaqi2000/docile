<?php

declare(strict_types=1);

namespace Docile\Security\RateLimit;

interface RateLimiterInterface
{
    /** Attempt to execute an action; returns true if allowed, false if rate-limited. */
    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool;

    /** Get remaining attempts for the key. */
    public function remaining(string $key, int $maxAttempts): int;

    /** Reset the rate limit for the key. */
    public function reset(string $key): void;
}

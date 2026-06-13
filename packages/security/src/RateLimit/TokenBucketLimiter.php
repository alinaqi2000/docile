<?php

declare(strict_types=1);

namespace Docile\Security\RateLimit;

use Psr\SimpleCache\CacheInterface;
use Psr\Clock\ClockInterface;

final class TokenBucketLimiter implements RateLimiterInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly ClockInterface $clock,
    ) {}

    #[\Override]
    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $now = $this->clock->now()->getTimestamp();
        $expiresAt = $now + $decaySeconds;

        $data = $this->cache->get($key);

        if (!is_array($data)) {
            $this->cache->set($key, ['attempts' => 1, 'expires_at' => $expiresAt], $decaySeconds);

            return true;
        }

        if (!isset($data['expires_at']) || !is_int($data['expires_at']) || $data['expires_at'] < $now) {
            $this->cache->set($key, ['attempts' => 1, 'expires_at' => $expiresAt], $decaySeconds);

            return true;
        }

        if (!isset($data['attempts']) || !is_int($data['attempts'])) {
            $this->cache->set($key, ['attempts' => 1, 'expires_at' => $expiresAt], $decaySeconds);

            return true;
        }

        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }

        $data['attempts']++;
        $this->cache->set($key, $data, $decaySeconds);

        return true;
    }

    #[\Override]
    public function remaining(string $key, int $maxAttempts): int
    {
        $data = $this->cache->get($key);

        if (!is_array($data)) {
            return $maxAttempts;
        }

        $now = $this->clock->now()->getTimestamp();

        if (!isset($data['expires_at']) || !is_int($data['expires_at']) || $data['expires_at'] < $now) {
            return $maxAttempts;
        }

        if (!isset($data['attempts']) || !is_int($data['attempts'])) {
            return $maxAttempts;
        }

        return max(0, $maxAttempts - $data['attempts']);
    }

    #[\Override]
    public function reset(string $key): void
    {
        $this->cache->delete($key);
    }
}

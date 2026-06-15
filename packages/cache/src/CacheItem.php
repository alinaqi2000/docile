<?php

declare(strict_types=1);

namespace Docile\Cache;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

use function is_int;

/**
 * PSR-6 cache item implementation.
 */
final class CacheItem implements CacheItemInterface
{
    private bool $isHit = false;

    /**
     * @param mixed $value
     */
    public function __construct(
        private readonly string $key,
        private mixed $value = null,
        private bool $hasValue = false,
        private DateTimeImmutable|null $expiresAt = null,
    ) {}

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * @param mixed $value
     */
    public function set(mixed $value, DateInterval|int|null $ttl = null): static
    {
        $this->value = $value;
        $this->hasValue = true;

        if ($ttl !== null) {
            $this->expiresAfter($ttl);
        }

        return $this;
    }

    /**
     * @param DateInterval|int|null $time
     */
    public function expiresAfter($time): static
    {
        if ($time === null) {
            $this->expiresAt = null;

            return $this;
        }

        if ($time instanceof DateInterval) {
            $this->expiresAt = (new DateTimeImmutable())->add($time)->setTimezone(new DateTimeZone('UTC'));

            return $this;
        }

        if (is_int($time)) {
            $this->expiresAt = (new DateTimeImmutable())->modify("+{$time} seconds")->setTimezone(new DateTimeZone('UTC'));

            return $this;
        }

        return $this;
    }

    /**
     * @param DateTimeInterface|null $expiration
     */
    public function expiresAt($expiration): static
    {
        if ($expiration === null) {
            $this->expiresAt = null;

            return $this;
        }

        if ($expiration instanceof DateTimeInterface) {
            $this->expiresAt = DateTimeImmutable::createFromInterface($expiration)->setTimezone(new DateTimeZone('UTC'));

            return $this;
        }

        return $this;
    }

    /**
     * Mark this item as a cache hit.
     */
    public function markAsHit(): void
    {
        $this->isHit = true;
    }

    /**
     * Check if this item has a value set.
     */
    public function hasValue(): bool
    {
        return $this->hasValue;
    }

    /**
     * Get the expiration timestamp or null if no expiration.
     */
    public function getExpiration(): DateTimeImmutable|null
    {
        return $this->expiresAt;
    }

    /**
     * Check if this item is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return (new DateTime('now', new DateTimeZone('UTC'))) > $this->expiresAt;
    }
}

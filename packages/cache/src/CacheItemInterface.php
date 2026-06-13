<?php

declare(strict_types=1);

namespace Docile\Cache;

use DateInterval;
use Psr\Cache\CacheItemInterface as PsrCacheItemInterface;

/**
 * Extended cache item interface with fluent setter.
 */
interface CacheItemInterface extends PsrCacheItemInterface
{
    /**
     * Set the value for this cache item.
     *
     * @param mixed $value
     */
    public function set(mixed $value, DateInterval|int|null $ttl = null): static;
}

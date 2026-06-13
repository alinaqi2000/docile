<?php

declare(strict_types=1);

namespace Docile\Cache\Backend;

use Docile\Cache\AbstractCachePool;
use Docile\Cache\CacheItem;

/**
 * Null cache backend that never stores anything.
 */
final class NullCache extends AbstractCachePool
{
    protected function doGet(string $key): CacheItem|null
    {
        return null;
    }

    protected function doSave(CacheItem $item): bool
    {
        return true;
    }

    /**
     * @param list<string> $keys
     */
    protected function doDelete(array $keys): bool
    {
        return true;
    }

    protected function doClear(): bool
    {
        return true;
    }

    protected function doHas(string $key): bool
    {
        return false;
    }
}

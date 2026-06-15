<?php

declare(strict_types=1);

namespace Docile\Cache;

use Docile\Cache\Exception\InvalidArgumentException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

use function is_string;
use function preg_match;

/**
 * Abstract PSR-6 cache pool with deferred item support.
 */
abstract class AbstractCachePool implements CacheItemPoolInterface
{
    /** @var array<string, CacheItem> */
    private array $deferred = [];

    /**
     * @param iterable<string> $keys
     *
     * @return iterable<string, CacheItemInterface>
     */
    public function getItems(iterable $keys = []): iterable
    {
        $items = [];

        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw InvalidArgumentException::invalidKey($key);
            }

            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    public function getItem(string $key): CacheItemInterface
    {
        $this->validateKey($key);

        $item = $this->doGet($key);

        if ($item === null) {
            return new CacheItem($key);
        }

        if ($item->isExpired()) {
            $this->doDelete([$key]);

            return new CacheItem($key);
        }

        $item->markAsHit();

        return $item;
    }

    public function hasItem(string $key): bool
    {
        $this->validateKey($key);

        return $this->doHas($key);
    }

    public function clear(): bool
    {
        $this->deferred = [];

        return $this->doClear();
    }

    public function deleteItem(string $key): bool
    {
        $this->validateKey($key);

        return $this->doDelete([$key]);
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteItems(iterable $keys): bool
    {
        $keyArray = [];

        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw InvalidArgumentException::invalidKey($key);
            }

            $keyArray[] = $key;
        }

        return $this->doDelete($keyArray);
    }

    public function save(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItem) {
            throw InvalidArgumentException::invalidItem($item);
        }

        return $this->doSave($item);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItem) {
            throw InvalidArgumentException::invalidItem($item);
        }

        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    public function commit(): bool
    {
        $success = true;

        foreach ($this->deferred as $item) {
            if (!$this->doSave($item)) {
                $success = false;
            }
        }

        $this->deferred = [];

        return $success;
    }

    /**
     * Validate a cache key according to PSR-6 requirements.
     */
    protected function validateKey(string $key): void
    {
        if ($key === '') {
            throw InvalidArgumentException::emptyKey();
        }

        if (preg_match('/[\{\}\(\)\/\\\\@:]/', $key) === 1) {
            throw InvalidArgumentException::invalidCharacters($key);
        }
    }

    /**
     * Get a cache item from storage, or null if not found.
     */
    abstract protected function doGet(string $key): CacheItem|null;

    /**
     * Save a cache item to storage.
     */
    abstract protected function doSave(CacheItem $item): bool;

    /**
     * Delete multiple keys from storage.
     *
     * @param list<string> $keys
     */
    abstract protected function doDelete(array $keys): bool;

    /**
     * Clear all items from storage.
     */
    abstract protected function doClear(): bool;

    /**
     * Check if an item exists in storage.
     */
    abstract protected function doHas(string $key): bool;
}

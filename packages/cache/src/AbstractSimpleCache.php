<?php

declare(strict_types=1);

namespace Docile\Cache;

use DateInterval;
use Docile\Cache\Exception\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Throwable;

use function is_int;

/**
 * Abstract PSR-16 cache implementation delegating to PSR-6 pool.
 */
abstract class AbstractSimpleCache implements SimpleCacheInterface
{
    private AbstractCachePool $pool;

    public function __construct(AbstractCachePool $pool)
    {
        $this->pool = $pool;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        try {
            $item = $this->pool->getItem($key);

            if (!$item->isHit()) {
                return $default;
            }

            return $item->get();
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw Exception\CacheException::fromThrowable($e);
        }
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);

        try {
            $item = new CacheItem($key);
            $item->set($value, $ttl);

            return $this->pool->save($item);
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw Exception\CacheException::fromThrowable($e);
        }
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);

        try {
            return $this->pool->deleteItem($key);
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw Exception\CacheException::fromThrowable($e);
        }
    }

    public function clear(): bool
    {
        try {
            return $this->pool->clear();
        } catch (Throwable $e) {
            throw Exception\CacheException::fromThrowable($e);
        }
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keyArray = [];

        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw InvalidArgumentException::invalidKey($key);
            }

            $keyArray[] = $key;
        }

        try {
            $items = $this->pool->getItems($keyArray);
            $result = [];

            foreach ($items as $item) {
                $result[$item->getKey()] = $item->isHit() ? $item->get() : $default;
            }

            return $result;
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw Exception\CacheException::fromThrowable($e);
        }
    }

    /**
     * @param iterable<string, mixed> $values
     * @phpstan-param iterable<mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw InvalidArgumentException::invalidKey($key);
            }

            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $keyArray = [];

        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw InvalidArgumentException::invalidKey($key);
            }

            $keyArray[] = $key;
        }

        try {
            return $this->pool->deleteItems($keyArray);
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw Exception\CacheException::fromThrowable($e);
        }
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);

        try {
            return $this->pool->hasItem($key);
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw Exception\CacheException::fromThrowable($e);
        }
    }

    /**
     * Validate a cache key according to PSR-16 requirements.
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
}

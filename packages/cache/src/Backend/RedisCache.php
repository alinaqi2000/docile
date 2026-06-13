<?php

declare(strict_types=1);

namespace Docile\Cache\Backend;

use Docile\Cache\AbstractCachePool;
use Docile\Cache\CacheItem;
use Docile\Cache\Exception\CacheException;
use Redis;

use function extension_loaded;
use function is_array;
use function is_string;
use function serialize;
use function unserialize;

/**
 * Redis cache backend using ext-redis.
 */
final class RedisCache extends AbstractCachePool
{
    private Redis $redis;
    private string $prefix;

    public function __construct(Redis $redis, string $prefix = '')
    {
        if (!extension_loaded('redis')) {
            throw CacheException::extensionNotLoaded('redis');
        }

        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    protected function doGet(string $key): CacheItem|null
    {
        $prefixedKey = $this->prefixedKey($key);
        $value = $this->redis->get($prefixedKey);

        if ($value === false || $value === null) {
            return null;
        }

        if (!is_string($value)) {
            return null;
        }

        $data = unserialize($value);

        if (!is_array($data)) {
            return null;
        }

        if ($data['expires'] !== null && time() > $data['expires']) {
            $this->redis->del($prefixedKey);

            return null;
        }

        $item = new CacheItem($key);
        $item->set($data['value']);

        if ($data['expires'] !== null && is_string($data['expires'])) {
            $expiration = \DateTimeImmutable::createFromFormat('U', $data['expires']);

            if ($expiration !== false) {
                $item->expiresAt($expiration);
            }
        }

        return $item;
    }

    protected function doSave(CacheItem $item): bool
    {
        $prefixedKey = $this->prefixedKey($item->getKey());
        $expires = $item->getExpiration();
        $expiresTimestamp = $expires !== null ? (int) $expires->format('U') : null;

        $data = [
            'value' => $item->get(),
            'expires' => $expiresTimestamp,
        ];

        if ($expiresTimestamp !== null) {
            $ttl = $expiresTimestamp - time();

            if ($ttl <= 0) {
                return $this->redis->del($prefixedKey) > 0;
            }

            return $this->redis->setex($prefixedKey, $ttl, serialize($data));
        }

        return $this->redis->set($prefixedKey, serialize($data));
    }

    /**
     * @param list<string> $keys
     */
    protected function doDelete(array $keys): bool
    {
        $prefixedKeys = [];

        foreach ($keys as $key) {
            $prefixedKeys[] = $this->prefixedKey($key);
        }

        return $this->redis->del($prefixedKeys) > 0;
    }

    protected function doClear(): bool
    {
        $pattern = $this->prefix . '*';
        $keys = $this->redis->keys($pattern);

        if ($keys === []) {
            return true;
        }

        return $this->redis->del($keys) > 0;
    }

    protected function doHas(string $key): bool
    {
        $prefixedKey = $this->prefixedKey($key);

        if (!$this->redis->exists($prefixedKey)) {
            return false;
        }

        $value = $this->redis->get($prefixedKey);

        if ($value === false || $value === null) {
            return false;
        }

        if (!is_string($value)) {
            return false;
        }

        $data = unserialize($value);

        if (!is_array($data)) {
            return false;
        }

        if ($data['expires'] !== null && time() > $data['expires']) {
            $this->redis->del($prefixedKey);

            return false;
        }

        return true;
    }

    private function prefixedKey(string $key): string
    {
        return $this->prefix . $key;
    }
}

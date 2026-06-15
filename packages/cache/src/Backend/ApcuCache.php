<?php

declare(strict_types=1);

namespace Docile\Cache\Backend;

use Docile\Cache\AbstractCachePool;
use Docile\Cache\CacheItem;
use Docile\Cache\Exception\CacheException;

use function apcu_clear_cache;
use function apcu_delete;
use function apcu_exists;
use function apcu_fetch;
use function apcu_store;
use function count;
use function extension_loaded;
use function is_array;
use function is_int;
use function is_string;
use function serialize;
use function unserialize;

/**
 * APCu cache backend.
 */
final class ApcuCache extends AbstractCachePool
{
    private string $prefix;

    public function __construct(string $prefix = '')
    {
        if (!extension_loaded('apcu')) {
            throw CacheException::extensionNotLoaded('apcu');
        }

        $this->prefix = $prefix;
    }

    protected function doGet(string $key): CacheItem|null
    {
        $prefixedKey = $this->prefixedKey($key);
        $value = apcu_fetch($prefixedKey, $success);

        if (!$success) {
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
            apcu_delete($prefixedKey);

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

        $ttl = $expiresTimestamp !== null ? $expiresTimestamp - time() : 0;

        return apcu_store($prefixedKey, serialize($data), $ttl);
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

        if (count($prefixedKeys) === 1) {
            return apcu_delete($prefixedKeys[0]);
        }

        $result = apcu_delete($prefixedKeys);

        if ($result === true) {
            return true;
        }

        if ($result === false) {
            return false;
        }

        return count($result) > 0;
    }

    protected function doClear(): bool
    {
        return apcu_clear_cache();
    }

    protected function doHas(string $key): bool
    {
        $prefixedKey = $this->prefixedKey($key);

        if (!apcu_exists($prefixedKey)) {
            return false;
        }

        $value = apcu_fetch($prefixedKey, $success);

        if (!$success) {
            return false;
        }

        if (!is_string($value)) {
            return false;
        }

        $data = unserialize($value);

        if (!is_array($data)) {
            return false;
        }

        if ($data['expires'] !== null && is_string($data['expires']) && time() > (int) $data['expires']) {
            apcu_delete($prefixedKey);

            return false;
        }

        return true;
    }

    private function prefixedKey(string $key): string
    {
        return $this->prefix . $key;
    }
}

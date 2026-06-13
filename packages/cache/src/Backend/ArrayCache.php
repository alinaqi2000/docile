<?php

declare(strict_types=1);

namespace Docile\Cache\Backend;

use Docile\Cache\AbstractCachePool;
use Docile\Cache\CacheItem;

use function microtime;
use function serialize;
use function unserialize;

/**
 * In-memory array cache backend for testing.
 */
final class ArrayCache extends AbstractCachePool
{
    /** @var array<string, array{value: string, expires: float|null}> */
    private array $storage = [];

    protected function doGet(string $key): CacheItem|null
    {
        if (!isset($this->storage[$key])) {
            return null;
        }

        $data = $this->storage[$key];

        if ($data['expires'] !== null && microtime(true) > $data['expires']) {
            unset($this->storage[$key]);

            return null;
        }

        $item = new CacheItem($key);
        $item->set(unserialize($data['value']));

        if ($data['expires'] !== null) {
            $expiration = \DateTimeImmutable::createFromFormat('U.u', (string) $data['expires']);

            if ($expiration !== false) {
                $item->expiresAt($expiration);
            }
        }

        return $item;
    }

    protected function doSave(CacheItem $item): bool
    {
        $expires = $item->getExpiration();
        $expiresTimestamp = $expires !== null ? (float) $expires->format('U.u') : null;

        $this->storage[$item->getKey()] = [
            'value' => serialize($item->get()),
            'expires' => $expiresTimestamp,
        ];

        return true;
    }

    /**
     * @param list<string> $keys
     */
    protected function doDelete(array $keys): bool
    {
        foreach ($keys as $key) {
            unset($this->storage[$key]);
        }

        return true;
    }

    protected function doClear(): bool
    {
        $this->storage = [];

        return true;
    }

    protected function doHas(string $key): bool
    {
        if (!isset($this->storage[$key])) {
            return false;
        }

        $data = $this->storage[$key];

        if ($data['expires'] !== null && microtime(true) > $data['expires']) {
            unset($this->storage[$key]);

            return false;
        }

        return true;
    }
}

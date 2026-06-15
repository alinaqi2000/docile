<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Fixtures;

use Psr\SimpleCache\CacheInterface;

final class ArrayCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $storage = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->storage[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->storage[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->storage[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->storage = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->storage[$key] ?? $default;
        }

        return $result;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->storage[$key] = $value;
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            unset($this->storage[$key]);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }
}

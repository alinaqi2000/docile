<?php

declare(strict_types=1);

namespace Docile\Config;

use ArrayAccess;
use Docile\Config\Exception\MissingKeyException;
use Docile\Config\Exception\TypeMismatchException;

use function array_key_exists;
use function array_shift;
use function explode;
use function get_debug_type;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/**
 * Central typed configuration store with dot-notation access.
 *
 * Internally stores config as a nested array. Dot-notation keys (e.g. "app.debug")
 * are resolved by traversing the nested structure at read/write time.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Repository implements ArrayAccess
{
    /** @var array<string, mixed> */
    private array $items;

    /**
     * @param array<string, mixed> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Load and merge all items from a loader into this repository.
     */
    public function load(LoaderInterface $loader): void
    {
        $this->merge($loader->load());
    }

    /**
     * Retrieve a value by dot-notation key. Returns $default when key is absent.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->dotGet($this->items, $key, $default);
    }

    /**
     * Set a value by dot-notation key.
     */
    public function set(string $key, mixed $value): void
    {
        $this->dotSet($this->items, $key, $value);
    }

    /**
     * Determine if a dot-notation key exists (and is not null).
     */
    public function has(string $key): bool
    {
        return $this->dotHas($this->items, $key);
    }

    /**
     * Return the value as a string. Falls back to $default if the key is absent.
     * Throws {@see TypeMismatchException} if the value is present but not a string.
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        if (!is_string($value)) {
            throw TypeMismatchException::forKey($key, 'string', get_debug_type($value));
        }

        return $value;
    }

    /**
     * Return the value as an int. Falls back to $default if the key is absent.
     * Throws {@see TypeMismatchException} if the value is present but not an int.
     */
    public function int(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        if (!is_int($value)) {
            throw TypeMismatchException::forKey($key, 'int', get_debug_type($value));
        }

        return $value;
    }

    /**
     * Return the value as a float. Falls back to $default if the key is absent.
     * Throws {@see TypeMismatchException} if the value is present but not a float or int.
     */
    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        if (!is_float($value)) {
            throw TypeMismatchException::forKey($key, 'float', get_debug_type($value));
        }

        return $value;
    }

    /**
     * Return the value as a bool. Falls back to $default if the key is absent.
     * Throws {@see TypeMismatchException} if the value is present but not a bool.
     */
    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        if (!is_bool($value)) {
            throw TypeMismatchException::forKey($key, 'bool', get_debug_type($value));
        }

        return $value;
    }

    /**
     * Return the value as an array. Falls back to $default if the key is absent.
     * Throws {@see TypeMismatchException} if the value is present but not an array.
     *
     * @param array<string, mixed> $default
     *
     * @return array<string, mixed>
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        if (!is_array($value)) {
            throw TypeMismatchException::forKey($key, 'array', get_debug_type($value));
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * Require a key to be present; throws {@see MissingKeyException} otherwise.
     *
     * @throws MissingKeyException
     */
    public function required(string $key): mixed
    {
        if (!$this->has($key)) {
            throw MissingKeyException::forKey($key);
        }

        return $this->get($key);
    }

    /**
     * Return the entire nested config array.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Deep-merge the given array into the repository.
     *
     * @param array<string, mixed> $items
     */
    public function merge(array $items): void
    {
        $this->items = $this->deepMerge($this->items, $items);
    }

    // -------------------------------------------------------------------------
    // ArrayAccess
    // -------------------------------------------------------------------------

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string) $offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->dotUnset($this->items, (string) $offset);
    }

    // -------------------------------------------------------------------------
    // Dot-notation helpers
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $items
     */
    private function dotGet(array $items, string $key, mixed $default): mixed
    {
        if (array_key_exists($key, $items)) {
            return $items[$key];
        }

        $segments = explode('.', $key);
        $current = $items;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }

            /** @var array<string, mixed>|mixed $current */
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @param array<string, mixed> $items
     */
    private function dotHas(array $items, string $key): bool
    {
        if (array_key_exists($key, $items)) {
            return true;
        }

        $segments = explode('.', $key);
        $current = $items;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }

            /** @var array<string, mixed>|mixed $current */
            $current = $current[$segment];
        }

        return true;
    }

    /**
     * @param array<string, mixed> $items
     */
    private function dotSet(array &$items, string $key, mixed $value): void
    {
        $segments = explode('.', $key);

        if (count($segments) === 1) {
            $items[$key] = $value;

            return;
        }

        $current = &$items;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $current[$segment] = $value;

                return;
            }

            if (!array_key_exists($segment, $current) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            /** @var array<string, mixed> $current */
            $current = &$current[$segment];
        }
    }

    /**
     * @param array<string, mixed> $items
     */
    private function dotUnset(array &$items, string $key): void
    {
        $segments = explode('.', $key);

        if (count($segments) === 1) {
            unset($items[$key]);

            return;
        }

        $current = &$items;
        $lastIndex = count($segments) - 1;

        foreach ($segments as $i => $segment) {
            if ($i === $lastIndex) {
                unset($current[$segment]);

                return;
            }

            if (!array_key_exists($segment, $current) || !is_array($current[$segment])) {
                return;
            }

            /** @var array<string, mixed> $current */
            $current = &$current[$segment];
        }
    }

    /**
     * Recursively merge $override onto $base (last write wins for scalars).
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function deepMerge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (
                isset($base[$key])
                && is_array($base[$key])
                && is_array($value)
            ) {
                /** @var array<string, mixed> $baseValue */
                $baseValue = $base[$key];
                /** @var array<string, mixed> $value */
                $base[$key] = $this->deepMerge($baseValue, $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}

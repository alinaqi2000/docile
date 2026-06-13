<?php

declare(strict_types=1);

namespace Docile\Support;

use Closure;

use function array_diff_key;
use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_merge;
use function array_reverse;
use function array_shift;
use function array_values;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_object;
use function property_exists;
use function str_contains;

/**
 * Array utility helpers — all static, pure, no global state.
 */
final class Arr
{
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param array<mixed> $array
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (!str_contains($key, '.')) {
            return $default;
        }

        $current = $array;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * @param array<mixed> $array
     */
    public static function set(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);

        if (count($keys) === 1) {
            $array[$key] = $value;

            return;
        }

        /** @var string $first */
        $first = array_shift($keys);
        $remaining = implode('.', $keys);

        if (!isset($array[$first]) || !is_array($array[$first])) {
            $array[$first] = [];
        }

        /** @var array<mixed> $nested */
        $nested = &$array[$first];
        self::set($nested, $remaining, $value);
    }

    /**
     * Check if an item exists in an array using "dot" notation.
     *
     * @param array<mixed> $array
     */
    public static function has(array $array, string $key): bool
    {
        if (array_key_exists($key, $array)) {
            return true;
        }

        if (!str_contains($key, '.')) {
            return false;
        }

        $current = $array;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }

            $current = $current[$segment];
        }

        return true;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param array<mixed> $array
     */
    public static function forget(array &$array, string $key): void
    {
        $keys = explode('.', $key);

        if (count($keys) === 1) {
            unset($array[$key]);

            return;
        }

        /** @var string $first */
        $first = array_shift($keys);
        $remaining = implode('.', $keys);

        if (!isset($array[$first]) || !is_array($array[$first])) {
            return;
        }

        /** @var array<mixed> $nested */
        $nested = &$array[$first];
        self::forget($nested, $remaining);
    }

    /**
     * Get a subset of the items from the given array by keys.
     *
     * @param array<mixed>       $array
     * @param array<int, string> $keys
     *
     * @return array<mixed>
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param array<mixed>       $array
     * @param array<int, string> $keys
     *
     * @return array<mixed>
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param array<mixed>  $array
     * @param Closure(mixed, mixed): bool|null $callback
     */
    public static function first(array $array, ?Closure $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if ($array === []) {
                return $default;
            }

            return array_values($array)[0];
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param array<mixed>  $array
     * @param Closure(mixed, mixed): bool|null $callback
     */
    public static function last(array $array, ?Closure $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if ($array === []) {
                return $default;
            }

            return array_values($array)[count($array) - 1];
        }

        return self::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param array<mixed> $array
     * @param float        $depth
     *
     * @return array<mixed>
     */
    public static function flatten(array $array, float $depth = INF): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1.0) {
                foreach ($item as $v) {
                    $result[] = $v;
                }
            } else {
                foreach (self::flatten($item, $depth - 1) as $v) {
                    $result[] = $v;
                }
            }
        }

        return $result;
    }

    /**
     * Flatten a multi-dimensional associative array using "dot" notation keys.
     *
     * @param array<mixed> $array
     *
     * @return array<string, mixed>
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            $fullKey = $prepend . $key;

            if (is_array($value) && $value !== []) {
                /** @var array<string, mixed> $nested */
                $nested = self::dot($value, $fullKey . '.');
                $results = array_merge($results, $nested);
            } else {
                $results[$fullKey] = $value;
            }
        }

        return $results;
    }

    /**
     * Convert a flattened "dot" notation array into an expanded array.
     *
     * @param array<string, mixed> $array
     *
     * @return array<mixed>
     */
    public static function undot(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            self::set($result, $key, $value);
        }

        return $result;
    }

    /**
     * Wrap the given value in an array if it is not already an array.
     *
     * Null values become an empty array.
     *
     * @return array<mixed>
     */
    public static function wrap(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param array<mixed>  $array
     * @param string        $value  Dot-notation path to the value field.
     * @param string|null   $key    Optional dot-notation path to use as the result key.
     *
     * @return array<mixed>
     */
    public static function pluck(array $array, string $value, ?string $key = null): array
    {
        $results = [];

        foreach ($array as $item) {
            /** @var mixed $itemValue */
            $itemValue = self::dataGet($item, $value);

            if ($key === null) {
                $results[] = $itemValue;
            } else {
                /** @var array-key $itemKey */
                $itemKey = self::dataGet($item, $key);
                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    // -----------------------------------------------------------------------
    // Internal helpers
    // -----------------------------------------------------------------------

    /**
     * Get a value from an item using dot notation (supports arrays and objects).
     */
    private static function dataGet(mixed $target, string $key): mixed
    {
        if (is_array($target)) {
            return self::get($target, $key);
        }

        if (is_object($target)) {
            $current = $target;

            foreach (explode('.', $key) as $segment) {
                if (!is_object($current) || !property_exists($current, $segment)) {
                    return null;
                }

                $current = $current->{$segment}; // @phpstan-ignore property.dynamicName
            }

            return $current;
        }

        return null;
    }
}

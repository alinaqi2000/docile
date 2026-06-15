<?php

declare(strict_types=1);

namespace Docile\Support;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;

use function array_chunk;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_reduce;
use function array_reverse;
use function array_slice;
use function array_values;
use function count;
use function in_array;
use function uasort;
use function usort;

/**
 * Immutable generic collection.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @implements IteratorAggregate<TKey, TValue>
 */
final class Collection implements IteratorAggregate, Countable, JsonSerializable
{
    /** @var array<TKey, TValue> */
    private readonly array $items;

    /**
     * @param array<TKey, TValue> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Create a new collection.
     *
     * @template MKey of array-key
     * @template MValue
     *
     * @param array<MKey, MValue> $items
     *
     * @return self<MKey, MValue>
     */
    public static function make(array $items = []): self
    {
        return new self($items);
    }

    /**
     * Apply a callback to every item and return a new collection.
     *
     * @template TNewValue
     *
     * @param Closure(TValue, TKey): TNewValue $callback
     *
     * @return self<TKey, TNewValue>
     */
    public function map(Closure $callback): self
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $result[$key] = $callback($value, $key);
        }

        /** @var self<TKey, TNewValue> */
        return new self($result);
    }

    /**
     * Filter items through a truth test.
     *
     * When no callback is provided, all falsy values are removed.
     *
     * @param Closure(TValue, TKey): bool|null $callback
     *
     * @return self<TKey, TValue>
     */
    public function filter(?Closure $callback = null): self
    {
        if ($callback === null) {
            return new self(array_filter($this->items, static fn (mixed $v): bool => (bool) $v));
        }

        $result = [];

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                $result[$key] = $value;
            }
        }

        return new self($result);
    }

    /**
     * Reduce the collection to a single value.
     *
     * @template TCarry
     *
     * @param Closure(TCarry, TValue): TCarry $callback
     * @param TCarry                          $initial
     *
     * @return TCarry
     */
    public function reduce(Closure $callback, mixed $initial = null): mixed
    {
        $carry = $initial;

        foreach ($this->items as $value) {
            $carry = $callback($carry, $value);
        }

        return $carry;
    }

    /**
     * Execute a callback over each item and return $this (read-only iteration).
     *
     * @param Closure(TValue, TKey): mixed $callback
     *
     * @return self<TKey, TValue>
     */
    public function each(Closure $callback): self
    {
        foreach ($this->items as $key => $value) {
            $callback($value, $key);
        }

        return $this;
    }

    /**
     * Return a new collection with sequential integer keys.
     *
     * @return self<int, TValue>
     */
    public function values(): self
    {
        /** @var self<int, TValue> */
        return new self(array_values($this->items));
    }

    /**
     * Return a new collection of the collection's keys.
     *
     * @return self<int, TKey>
     */
    public function keys(): self
    {
        /** @var self<int, TKey> */
        return new self(array_keys($this->items));
    }

    /**
     * Get the first item, optionally matching a predicate.
     *
     * @param Closure(TValue, TKey): bool|null $callback
     *
     * @return TValue|mixed
     */
    public function first(?Closure $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if ($this->items === []) {
                return $default;
            }

            return array_values($this->items)[0];
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get the last item, optionally matching a predicate.
     *
     * @param Closure(TValue, TKey): bool|null $callback
     *
     * @return TValue|mixed
     */
    public function last(?Closure $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if ($this->items === []) {
                return $default;
            }

            $values = array_values($this->items);

            return $values[count($values) - 1];
        }

        return (new self(array_reverse($this->items, true)))->first($callback, $default);
    }

    /**
     * Determine if the collection contains a given value or matches a predicate.
     *
     * @param TValue|Closure(TValue, TKey): bool $value
     */
    public function contains(mixed $value): bool
    {
        if ($value instanceof Closure) {
            foreach ($this->items as $key => $item) {
                if ($value($item, $key)) {
                    return true;
                }
            }

            return false;
        }

        return in_array($value, $this->items, true);
    }

    /**
     * Sort the collection using a callback or natural sort.
     *
     * @param Closure(TValue, TValue): int|null $callback
     *
     * @return self<TKey, TValue>
     */
    public function sort(?Closure $callback = null): self
    {
        $items = $this->items;

        if ($callback !== null) {
            uasort($items, $callback);
        } else {
            asort($items);
        }

        return new self($items);
    }

    /**
     * Sort the collection by a key or a closure.
     *
     * @param string|Closure(TValue): mixed $key
     *
     * @return self<TKey, TValue>
     */
    public function sortBy(string|Closure $key): self
    {
        $items = $this->items;

        uasort($items, static function (mixed $a, mixed $b) use ($key): int {
            /** @var mixed $aVal */
            $aVal = $key instanceof Closure ? $key($a) : Arr::get((array) $a, $key);
            /** @var mixed $bVal */
            $bVal = $key instanceof Closure ? $key($b) : Arr::get((array) $b, $key);

            return $aVal <=> $bVal;
        });

        return new self($items);
    }

    /**
     * Group the collection's items by a key or closure.
     *
     * @param string|Closure(TValue, TKey): array-key $key
     *
     * @return self<string, self<int, TValue>>
     */
    public function groupBy(string|Closure $key): self
    {
        /** @var array<string, array<int, TValue>> $result */
        $result = [];

        foreach ($this->items as $itemKey => $value) {
            /** @var array-key $groupKey */
            $groupKey = $key instanceof Closure
                ? $key($value, $itemKey)
                : Arr::get((array) $value, $key);

            $result[(string) $groupKey][] = $value;
        }

        /** @var array<string, self<int, TValue>> $grouped */
        $grouped = [];

        foreach ($result as $groupKey => $groupItems) {
            $grouped[$groupKey] = new self($groupItems);
        }

        /** @var self<string, self<int, TValue>> */
        return new self($grouped);
    }

    /**
     * Chunk the collection into collections of the given size.
     *
     * @return self<int, self<TKey, TValue>>
     */
    public function chunk(int $size): self
    {
        /** @var array<int, self<TKey, TValue>> $chunks */
        $chunks = [];

        $safeSize = max(1, $size);

        foreach (array_chunk($this->items, $safeSize, true) as $chunk) {
            $chunks[] = new self($chunk);
        }

        /** @var self<int, self<TKey, TValue>> */
        return new self($chunks);
    }

    /**
     * Get the underlying array.
     *
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Count the number of items.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Serialize to JSON-encodable value.
     *
     * @return array<TKey, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }

    /**
     * Get an iterator for the items.
     *
     * @return ArrayIterator<TKey, TValue>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}

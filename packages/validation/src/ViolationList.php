<?php

declare(strict_types=1);

namespace Docile\Validation;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/** @implements IteratorAggregate<string, array<string>> */
final class ViolationList implements Countable, IteratorAggregate
{
    /** @param array<string, array<string>> $violations */
    public function __construct(private array $violations = []) {}

    public function add(string $field, string $message): void
    {
        $this->violations[$field][] = $message;
    }

    public function has(string $field): bool
    {
        return isset($this->violations[$field]) && $this->violations[$field] !== [];
    }

    /** @return array<string> */
    public function get(string $field): array
    {
        return $this->violations[$field] ?? [];
    }

    /** @return array<string, array<string>> */
    public function all(): array
    {
        return $this->violations;
    }

    public function isEmpty(): bool
    {
        return $this->violations === [];
    }

    public function count(): int
    {
        /** @var int<0, max> $result */
        $result = array_reduce($this->violations, static fn (int $carry, array $messages): int => $carry + count($messages), 0);
        return $result;
    }

    public function merge(self $other): void
    {
        foreach ($other->violations as $field => $messages) {
            foreach ($messages as $message) {
                $this->add($field, $message);
            }
        }
    }

    /** @return ArrayIterator<string, array<string>> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->violations);
    }
}
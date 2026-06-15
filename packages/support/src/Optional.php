<?php

declare(strict_types=1);

namespace Docile\Support;

use Closure;
use Docile\Support\Exception\OptionalIsNoneException;

/**
 * A simple Option/Maybe monad.
 *
 * An Optional<T> is either:
 *   - Some(value) — holds a value of type T
 *   - None        — holds no value
 *
 * @template T
 */
final class Optional
{
    /** @var T */
    private readonly mixed $value;

    private readonly bool $hasValue;

    /**
     * @param T    $value
     * @param bool $hasValue
     */
    private function __construct(mixed $value, bool $hasValue)
    {
        $this->value = $value;
        $this->hasValue = $hasValue;
    }

    /**
     * Wrap a value in a Some Optional.
     *
     * @template U
     *
     * @param U $value
     *
     * @return self<U>
     */
    public static function some(mixed $value): self
    {
        /** @var self<U> */
        return new self($value, true);
    }

    /**
     * Return a None Optional.
     *
     * @return self<never>
     */
    public static function none(): self
    {
        /** @var self<never> */
        return new self(null, false);
    }

    /**
     * Return true when this Optional holds a value.
     */
    public function isSome(): bool
    {
        return $this->hasValue;
    }

    /**
     * Return true when this Optional holds no value.
     */
    public function isNone(): bool
    {
        return !$this->hasValue;
    }

    /**
     * Unwrap the value, throwing if this is None.
     *
     * @return T
     *
     * @throws OptionalIsNoneException
     */
    public function get(): mixed
    {
        if (!$this->hasValue) {
            throw OptionalIsNoneException::create();
        }

        return $this->value;
    }

    /**
     * Unwrap the value or return a default.
     *
     * @template D
     *
     * @param D $default
     *
     * @return T|D
     */
    public function getOrElse(mixed $default): mixed
    {
        if (!$this->hasValue) {
            return $default;
        }

        return $this->value;
    }

    /**
     * Apply a function to the value if Some, returning a new Optional<U>.
     * If None, return None.
     *
     * @template U
     *
     * @param Closure(T): U $fn
     *
     * @return self<U>
     */
    public function map(Closure $fn): self
    {
        if (!$this->hasValue) {
            return self::none();
        }

        return self::some($fn($this->value));
    }

    /**
     * Apply a function that returns an Optional. If this is None, returns None.
     *
     * @template U
     *
     * @param Closure(T): self<U> $fn
     *
     * @return self<U>
     */
    public function flatMap(Closure $fn): self
    {
        if (!$this->hasValue) {
            return self::none();
        }

        return $fn($this->value);
    }
}

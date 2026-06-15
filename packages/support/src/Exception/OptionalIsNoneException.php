<?php

declare(strict_types=1);

namespace Docile\Support\Exception;

use RuntimeException;

/**
 * Thrown when attempting to unwrap a None Optional value.
 */
final class OptionalIsNoneException extends RuntimeException
{
    public static function create(): self
    {
        return new self('Cannot get value from a None Optional.');
    }
}

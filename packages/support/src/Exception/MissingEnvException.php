<?php

declare(strict_types=1);

namespace Docile\Support\Exception;

use RuntimeException;

/**
 * Thrown when a required environment variable is not set.
 */
final class MissingEnvException extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf('Required environment variable [%s] is not set.', $key));
    }
}

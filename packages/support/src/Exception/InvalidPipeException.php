<?php

declare(strict_types=1);

namespace Docile\Support\Exception;

use InvalidArgumentException;

/**
 * Thrown when a pipe passed to Pipeline is not a valid type.
 */
final class InvalidPipeException extends InvalidArgumentException
{
    public static function forPipe(mixed $pipe): self
    {
        $type = get_debug_type($pipe);

        return new self(sprintf(
            'Each pipe must be a Closure or a class-string with a handle() method; got [%s].',
            $type,
        ));
    }

    public static function missingHandle(string $class): self
    {
        return new self(sprintf(
            'Pipe class [%s] must have a public handle(mixed $payload, Closure $next): mixed method.',
            $class,
        ));
    }
}

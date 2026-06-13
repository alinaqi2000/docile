<?php

declare(strict_types=1);

namespace Docile\Foundation\Exception;

use RuntimeException;

final class FoundationException extends RuntimeException
{
    public static function handlerNotFound(string $handlerClass): self
    {
        return new self(
            sprintf('Request handler "%s" could not be resolved from the container.', $handlerClass),
        );
    }

    public static function invalidHandler(string $handlerClass): self
    {
        return new self(
            sprintf('Resolved class "%s" does not implement RequestHandlerInterface.', $handlerClass),
        );
    }

    public static function consoleKernelNotFound(): self
    {
        return new self('Docile\Console\Kernel could not be resolved from the container.');
    }
}

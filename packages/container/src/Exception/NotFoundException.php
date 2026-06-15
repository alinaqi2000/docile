<?php

declare(strict_types=1);

namespace Docile\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function sprintf;

/**
 * Thrown by {@see \Docile\Container\Container::get()} when no entry exists for the given identifier.
 */
final class NotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
    public static function forId(string $id): self
    {
        return new self(sprintf('No entry was found in the container for identifier "%s".', $id));
    }
}

<?php

declare(strict_types=1);

namespace Docile\Cache\Exception;

use Docile\Cache\CacheItem;
use InvalidArgumentException as PhpInvalidArgumentException;
use Psr\Cache\InvalidArgumentException as PsrCacheInvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrSimpleCacheInvalidArgumentException;

use function get_debug_type;

/**
 * Thrown when an invalid argument is provided to a cache operation.
 */
final class InvalidArgumentException extends PhpInvalidArgumentException implements PsrCacheInvalidArgumentException, PsrSimpleCacheInvalidArgumentException
{
    public static function invalidKey(mixed $key): self
    {
        return new self('Cache key must be a string, ' . get_debug_type($key) . ' given.');
    }

    public static function emptyKey(): self
    {
        return new self('Cache key cannot be empty.');
    }

    public static function invalidCharacters(string $key): self
    {
        return new self('Cache key "' . $key . '" contains invalid characters.');
    }

    public static function invalidItem(object $item): self
    {
        return new self('Cache item must be an instance of ' . CacheItem::class . '.');
    }
}

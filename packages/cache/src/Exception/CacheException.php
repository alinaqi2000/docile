<?php

declare(strict_types=1);

namespace Docile\Cache\Exception;

use Psr\Cache\CacheException as PsrCacheException;
use RuntimeException;
use Throwable;

/**
 * Base exception for all Docile Cache errors.
 */
class CacheException extends RuntimeException implements PsrCacheException
{
    public static function fromThrowable(Throwable $e): self
    {
        return new self($e->getMessage(), $e->getCode(), $e);
    }

    public static function readFailure(string $filename): self
    {
        return new self('Failed to read cache file: ' . $filename);
    }

    public static function writeFailure(string $filename): self
    {
        return new self('Failed to write cache file: ' . $filename);
    }

    public static function extensionNotLoaded(string $extension): self
    {
        return new self('Required extension ' . $extension . ' is not loaded.');
    }
}

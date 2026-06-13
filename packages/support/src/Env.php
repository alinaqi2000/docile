<?php

declare(strict_types=1);

namespace Docile\Support;

use Docile\Support\Exception\MissingEnvException;

use function array_key_exists;
use function getenv;
use function in_array;
use function is_bool;
use function is_scalar;
use function mb_strtolower;

/**
 * Reads environment variables from $_ENV / $_SERVER (no dotenv dependency).
 *
 * Supports type coercion for common scalar types.
 */
final class Env
{
    /**
     * Get an environment variable value, returning a default if not set.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::readRaw($key) ?? $default;
    }

    /**
     * Get an environment variable as a string.
     */
    public static function string(string $key, string $default = ''): string
    {
        $value = self::readRaw($key);

        if ($value === null) {
            return $default;
        }

        if (!is_scalar($value)) {
            return $default;
        }

        return (string) $value;
    }

    /**
     * Get an environment variable as an integer.
     */
    public static function int(string $key, int $default = 0): int
    {
        $value = self::readRaw($key);

        if ($value === null) {
            return $default;
        }

        if (!is_scalar($value)) {
            return $default;
        }

        return (int) $value;
    }

    /**
     * Get an environment variable as a boolean.
     *
     * The strings "true", "1", "yes", "on" are truthy; "false", "0", "no", "off", "" are falsy.
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::readRaw($key);

        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (!is_scalar($value)) {
            return $default;
        }

        $lower = mb_strtolower((string) $value);

        if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($lower, ['false', '0', 'no', 'off', ''], true)) {
            return false;
        }

        return (bool) $value;
    }

    /**
     * Get a required environment variable; throws if not set.
     *
     * @throws MissingEnvException
     */
    public static function required(string $key): mixed
    {
        $value = self::readRaw($key);

        if ($value === null) {
            throw MissingEnvException::forKey($key);
        }

        return $value;
    }

    // -----------------------------------------------------------------------
    // Internal helpers
    // -----------------------------------------------------------------------

    /**
     * Read a raw value from $_ENV, $_SERVER, or getenv().
     *
     * Returns null if the key is not present in any source.
     */
    private static function readRaw(string $key): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Docile\Support;

use function array_map;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function lcfirst;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function random_bytes;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function ucfirst;
use function ucwords;

/**
 * String utility helpers — all static, no state.
 */
final class Str
{
    /**
     * Convert a string to StudlyCase (PascalCase).
     *
     * Example: "hello_world" → "HelloWorld"
     */
    public static function studly(string $value): string
    {
        $value = (string) preg_replace('/[-_\s]+/', ' ', $value);

        return (string) str_replace(' ', '', ucwords($value));
    }

    /**
     * Convert a string to camelCase.
     *
     * Example: "hello_world" → "helloWorld"
     */
    public static function camel(string $value): string
    {
        return lcfirst(self::studly($value));
    }

    /**
     * Convert a string to snake_case.
     *
     * Example: "HelloWorld" → "hello_world"
     */
    public static function snake(string $value): string
    {
        // Insert underscore before uppercase letters following lowercase/digits
        $value = (string) preg_replace_callback('/([a-z\d])([A-Z])/', static fn (array $m): string => $m[1] . '_' . $m[2], $value);
        // Insert underscore before runs of uppercase followed by a lowercase (e.g. "HTMLParser" → "HTML_Parser")
        $value = (string) preg_replace_callback('/([A-Z]+)([A-Z][a-z])/', static fn (array $m): string => $m[1] . '_' . $m[2], $value);

        return mb_strtolower((string) preg_replace('/[-\s]+/', '_', $value));
    }

    /**
     * Convert a string to kebab-case.
     *
     * Example: "HelloWorld" → "hello-world"
     */
    public static function kebab(string $value): string
    {
        return (string) str_replace('_', '-', self::snake($value));
    }

    /**
     * Convert a string to Title Case.
     *
     * Example: "hello world" → "Hello World"
     */
    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE);
    }

    /**
     * Generate a URL-safe slug from a string.
     *
     * Example: "Hello, World!" → "hello-world"
     */
    public static function slug(string $value, string $separator = '-'): string
    {
        // Transliterate accented characters to ASCII equivalents
        $value = self::ascii($value);

        // Replace non-alphanumeric characters with the separator
        $value = (string) preg_replace('/[^a-z0-9]+/i', $separator, $value);

        // Strip leading/trailing separators and collapse duplicates
        $quotedSep = preg_quote($separator, '/');
        $value = (string) preg_replace('/^' . $quotedSep . '+|' . $quotedSep . '+$/', '', $value);
        $value = (string) preg_replace('/' . $quotedSep . '+/', $separator, $value);

        return mb_strtolower($value);
    }

    /**
     * Determine if a string starts with a given substring or one of an array of substrings.
     *
     * @param string|array<int, string> $needles
     */
    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a string ends with a given substring or one of an array of substrings.
     *
     * @param string|array<int, string> $needles
     */
    public static function endsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a string contains a given substring or one of an array of substrings.
     *
     * @param string|array<int, string> $needles
     */
    public static function contains(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Limit the number of characters in a string.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit) . $end;
    }

    /**
     * Generate a cryptographically random alphanumeric string of the given length.
     */
    public static function random(int $length = 16): string
    {
        $string = '';
        $byteCount = max(1, (int) ceil($length * 3 / 4) + 8);
        $bytes = random_bytes($byteCount);
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = strlen($chars) - 1;

        for ($i = 0, $byteLen = strlen($bytes); $i < $byteLen && strlen($string) < $length; ++$i) {
            $index = ord($bytes[$i]) % 62;
            if ($index > $max) {
                continue;
            }

            $string .= $chars[$index];
        }

        // If we didn't get enough characters (very unlikely), recurse
        if (strlen($string) < $length) {
            return $string . self::random($length - strlen($string));
        }

        return substr($string, 0, $length);
    }

    /**
     * Generate a UUID v4 (random) string.
     */
    public static function uuid(): string
    {
        $bytes = random_bytes(16);

        // Set version 4 bits (bits 12-15 of time_hi_and_version)
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        // Set variant bits (bits 6-7 of clock_seq_hi_and_reserved)
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(substr($bytes, 0, 4)),
            bin2hex(substr($bytes, 4, 2)),
            bin2hex(substr($bytes, 6, 2)),
            bin2hex(substr($bytes, 8, 2)),
            bin2hex(substr($bytes, 10, 6)),
        );
    }

    /**
     * Generate a ULID (Universally Unique Lexicographically Sortable Identifier).
     *
     * A ULID is a 128-bit identifier: 48-bit timestamp (ms) + 80-bit random.
     * Encoded in Crockford base32 for 26 characters.
     */
    public static function ulid(): string
    {
        // 48-bit Unix timestamp in milliseconds (always non-negative)
        $now = max(0, (int) (microtime(true) * 1000));

        // 80 random bits = 10 bytes
        $randomBytes = random_bytes(10);

        // Encode: 10 characters for time, 16 for random
        return self::encodeCrockford($now, 10) . self::encodeBytesCrockford($randomBytes, 16);
    }

    /**
     * Mask a portion of a string with a given character.
     *
     * @param string $value  The input string.
     * @param string $char   The masking character (only the first char is used).
     * @param int    $start  Start offset (negative values count from the end).
     * @param int|null $length Number of characters to mask (null = mask to end).
     */
    public static function mask(string $value, string $char = '*', int $start = 0, ?int $length = null): string
    {
        $strLen = mb_strlen($value);

        // Normalise negative start
        $absStart = $start >= 0 ? $start : max(0, $strLen + $start);

        if ($length === null) {
            $maskLen = $strLen - $absStart;
        } elseif ($length < 0) {
            $maskLen = max(0, $strLen + $length - $absStart);
        } else {
            $maskLen = $length;
        }

        $maskLen = max(0, min($maskLen, $strLen - $absStart));

        if ($maskLen === 0) {
            return $value;
        }

        $maskChar = $char !== '' ? mb_substr($char, 0, 1) : '*';
        $mask = str_repeat($maskChar, $maskLen);

        return mb_substr($value, 0, $absStart) . $mask . mb_substr($value, $absStart + $maskLen);
    }

    // -----------------------------------------------------------------------
    // Internal helpers
    // -----------------------------------------------------------------------

    /**
     * Transliterate a string to its ASCII equivalent (best-effort).
     */
    private static function ascii(string $value): string
    {
        // Use iconv if available for best transliteration
        if (function_exists('iconv')) {
            $result = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($result !== false) {
                return $result;
            }
        }

        // Fallback: strip non-ASCII characters
        return (string) preg_replace('/[^\x00-\x7F]/u', '', $value);
    }

    /**
     * Encode an integer as a Crockford Base32 string of fixed length.
     *
     * @param int<0, max> $value
     * @param int<1, max> $chars
     */
    private static function encodeCrockford(int $value, int $chars): string
    {
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $result = '';

        for ($i = $chars - 1; $i >= 0; --$i) {
            $result = $alphabet[$value & 0x1F] . $result;
            $value >>= 5;
        }

        return $result;
    }

    /**
     * Encode a raw byte string as a Crockford Base32 string of fixed length.
     *
     * @param int<1, max> $chars
     */
    private static function encodeBytesCrockford(string $bytes, int $chars): string
    {
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $result = '';
        $value = 0;
        $bits = 0;

        for ($i = 0, $len = strlen($bytes); $i < $len; ++$i) {
            $value = ($value << 8) | ord($bytes[$i]);
            $bits += 8;

            while ($bits >= 5) {
                $bits -= 5;
                $result .= $alphabet[($value >> $bits) & 0x1F];
            }
        }

        // Handle any remaining bits
        if ($bits > 0) {
            $result .= $alphabet[($value << (5 - $bits)) & 0x1F];
        }

        // Pad or trim to exact length
        $result = str_pad($result, $chars, '0', STR_PAD_LEFT);

        return substr($result, -$chars);
    }
}

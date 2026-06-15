<?php

declare(strict_types=1);

namespace Docile\Security\Csrf;

use function is_string;

final class CsrfTokenManager
{
    private const string SESSION_KEY = '_csrf_token';

    /** Generate a new CSRF token and store it in the session. */
    /** @param array<string, mixed> $session */
    public function generate(array &$session): string
    {
        $token = bin2hex(random_bytes(32));
        $session[self::SESSION_KEY] = $token;

        return $token;
    }

    /** Validate a CSRF token against the session. */
    /** @param array<string, mixed> $session */
    public function validate(string $token, array $session): bool
    {
        $stored = $session[self::SESSION_KEY] ?? null;

        if ($stored === null || !is_string($stored)) {
            return false;
        }

        return hash_equals($stored, $token);
    }
}

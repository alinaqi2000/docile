<?php

declare(strict_types=1);

namespace Docile\Security\Auth;

use Docile\Security\Password\HasherInterface;

use function is_int;
use function is_string;

final class SessionGuard
{
    private const string SESSION_KEY = '_auth_user_id';

    public function __construct(
        private readonly UserProviderInterface $provider,
        private readonly HasherInterface $hasher,
    ) {}

    /** Attempt to authenticate a user with credentials. */
    /** @param array<string, string> $credentials */
    /** @param array<string, mixed> $session */
    public function attempt(array $credentials, array &$session): bool // @phpstan-ignore-line
    {
        $user = $this->provider->findByCredentials($credentials); // @phpstan-ignore-line

        if ($user === null) {
            return false;
        }

        $plain = $credentials['password'] ?? '';
        if (!is_string($plain) || !$this->hasher->verify($plain, $user->getPasswordHash())) {
            return false;
        }

        $session[self::SESSION_KEY] = $user->getId();

        return true;
    }

    /** Get the authenticated user from the session. */
    /** @param array<string, mixed> $session */
    public function user(array $session): ?UserInterface
    {
        $id = $session[self::SESSION_KEY] ?? null;

        if ($id === null) {
            return null;
        }

        if (!is_int($id) && !is_string($id)) {
            return null;
        }

        return $this->provider->findById($id);
    }

    /** Log the user out by clearing the session. */
    /** @param array<string, mixed> $session */
    public function logout(array &$session): void
    {
        unset($session[self::SESSION_KEY]);
    }
}

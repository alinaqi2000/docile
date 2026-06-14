<?php

declare(strict_types=1);

namespace Docile\Security\Auth;

use Docile\Security\Exception\AuthenticationException;

final class ApiKeyGuard
{
    private const string HEADER_NAME = 'X-API-Key';

    public function __construct(
        private readonly UserProviderInterface $provider,
    ) {}

    /** Authenticate a user from request headers. */
    /** @param array<string, string> $headers */
    public function authenticate(array $headers): ?UserInterface
    {
        $apiKey = $headers[self::HEADER_NAME] ?? null;

        if ($apiKey === null || $apiKey === '') {
            return null;
        }

        try {
            return $this->authenticateByKey($apiKey);
        } catch (AuthenticationException) {
            return null;
        }
    }

    /** Authenticate a user by API key directly. */
    public function authenticateByKey(string $apiKey): UserInterface
    {
        $user = $this->provider->findByCredentials(['api_key' => $apiKey]);

        if ($user === null) {
            throw new AuthenticationException('Invalid API key.');
        }

        return $user;
    }
}

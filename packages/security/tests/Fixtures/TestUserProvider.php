<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Fixtures;

use Docile\Security\Auth\UserInterface;
use Docile\Security\Auth\UserProviderInterface;

final class TestUserProvider implements UserProviderInterface
{
    /** @var array<int|string, TestUser> */
    private array $users = [];

    /** @var array<string, TestUser> */
    private array $usersByEmail = [];

    /** @var array<string, TestUser> */
    private array $usersByApiKey = [];

    public function addUser(TestUser $user): void
    {
        $this->users[$user->getId()] = $user;
        $this->usersByEmail[$user->getAuthIdentifier()] = $user;

        if ($user->getApiKey() !== null) {
            $this->usersByApiKey[$user->getApiKey()] = $user;
        }
    }

    public function findById(int|string $id): ?UserInterface
    {
        return $this->users[$id] ?? null;
    }

    /** @param array<string, string> $credentials */
    public function findByCredentials(array $credentials): ?UserInterface
    {
        $email = $credentials['email'] ?? null;

        if ($email !== null) {
            return $this->usersByEmail[$email] ?? null;
        }

        $apiKey = $credentials['api_key'] ?? null;

        if ($apiKey !== null) {
            return $this->usersByApiKey[$apiKey] ?? null;
        }

        return null;
    }
}

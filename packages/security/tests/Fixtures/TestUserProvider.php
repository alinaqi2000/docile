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

    public function addUser(TestUser $user): void
    {
        $this->users[$user->getId()] = $user;
        $this->usersByEmail[$user->getAuthIdentifier()] = $user;
    }

    public function findById(int|string $id): ?UserInterface
    {
        return $this->users[$id] ?? null;
    }

    /** @param array<string, string> $credentials */
    public function findByCredentials(array $credentials): ?UserInterface
    {
        $email = $credentials['email'] ?? null;

        if ($email === null) {
            return null;
        }

        return $this->usersByEmail[$email] ?? null;
    }
}

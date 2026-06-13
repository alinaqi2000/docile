<?php

declare(strict_types=1);

namespace Docile\Security\Auth;

interface UserProviderInterface
{
    /** Find a user by their unique identifier. */
    public function findById(int|string $id): ?UserInterface;

    /** Find a user by credentials (e.g. email + password). */
    /** @param array<string, string> $credentials */
    public function findByCredentials(array $credentials): ?UserInterface;
}

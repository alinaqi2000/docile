<?php

declare(strict_types=1);

namespace Docile\Security\Auth;

interface UserInterface
{
    /** Get the user's unique identifier (int or string). */
    public function getId(): int|string;

    /** Get the auth identifier used for looking up the user. */
    public function getAuthIdentifier(): string;

    /** Get the user's password hash. */
    public function getPasswordHash(): string;
}

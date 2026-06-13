<?php

declare(strict_types=1);

namespace Docile\Security\Password;

interface HasherInterface
{
    /** Hash a plain-text password. */
    public function hash(string $plain): string;

    /** Verify a plain-text password against a stored hash. */
    public function verify(string $plain, string $hash): bool;

    /** Determine if the hash needs to be rehashed (e.g. cost factor changed). */
    public function needsRehash(string $hash): bool;
}

<?php

declare(strict_types=1);

namespace Docile\Security\Password;

use InvalidArgumentException;
use Override;
use RuntimeException;

final class BcryptHasher implements HasherInterface
{
    private const int DEFAULT_COST = 12;

    public function __construct(
        private readonly int $cost = self::DEFAULT_COST,
    ) {
        if ($cost < 4 || $cost > 31) {
            throw new InvalidArgumentException('Bcrypt cost must be between 4 and 31.');
        }
    }

    #[Override]
    public function hash(string $plain): string
    {
        $hash = password_hash($plain, PASSWORD_BCRYPT, ['cost' => $this->cost]);

        if ($hash === false) { // @phpstan-ignore-line
            throw new RuntimeException('Failed to hash password.');
        }

        return $hash;
    }

    #[Override]
    public function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    #[Override]
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }
}

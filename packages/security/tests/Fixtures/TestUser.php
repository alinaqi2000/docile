<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Fixtures;

use Docile\Security\Auth\UserInterface;

final class TestUser implements UserInterface
{
    public function __construct(
        private readonly int $id,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly ?string $apiKey = null,
    ) {}

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getAuthIdentifier(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }
}

<?php

declare(strict_types=1);

namespace Docile\Bus\Tests\Fixtures;

final readonly class CreateUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}

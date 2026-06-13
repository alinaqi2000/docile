<?php

declare(strict_types=1);

namespace Docile\Bus\Tests\Fixtures;

use Docile\Bus\Attribute\AsCommandHandler;

#[AsCommandHandler]
final class CreateUserHandler
{
    public function __invoke(CreateUserCommand $command): string
    {
        return 'created:' . $command->name;
    }
}

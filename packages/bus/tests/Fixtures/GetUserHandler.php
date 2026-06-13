<?php

declare(strict_types=1);

namespace Docile\Bus\Tests\Fixtures;

use Docile\Bus\Attribute\AsQueryHandler;

#[AsQueryHandler]
final class GetUserHandler
{
    /**
     * @return array{id: int, name: string}
     */
    public function __invoke(GetUserQuery $query): array
    {
        return ['id' => $query->id, 'name' => 'User ' . $query->id];
    }
}

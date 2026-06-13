<?php

declare(strict_types=1);

namespace Docile\Bus\Tests\Fixtures;

final readonly class GetUserQuery
{
    public function __construct(public int $id) {}
}

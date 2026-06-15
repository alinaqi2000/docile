<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

use Docile\Container\Attribute\Inject;

final class InjectExample
{
    public function __construct(
        #[Inject('app.name')]
        public readonly string $appName,
    ) {}
}

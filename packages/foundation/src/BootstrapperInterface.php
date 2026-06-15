<?php

declare(strict_types=1);

namespace Docile\Foundation;

interface BootstrapperInterface
{
    public function bootstrap(Application $app): void;
}

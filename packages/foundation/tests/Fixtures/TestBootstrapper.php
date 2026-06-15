<?php

declare(strict_types=1);

namespace Docile\Foundation\Tests\Fixtures;

use Docile\Foundation\Application;
use Docile\Foundation\BootstrapperInterface;

/**
 * Records that it was called during bootstrap.
 */
final class TestBootstrapper implements BootstrapperInterface
{
    public bool $wasCalled = false;

    public function bootstrap(Application $app): void
    {
        $this->wasCalled = true;
    }
}

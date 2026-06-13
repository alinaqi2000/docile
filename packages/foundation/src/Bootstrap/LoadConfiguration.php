<?php

declare(strict_types=1);

namespace Docile\Foundation\Bootstrap;

use Docile\Config\DirectoryLoader;
use Docile\Config\Repository;
use Docile\Foundation\Application;
use Docile\Foundation\BootstrapperInterface;

/**
 * Loads all PHP configuration files from the given directory and registers
 * the resulting Config\Repository as a singleton 'config' in the container.
 */
final class LoadConfiguration implements BootstrapperInterface
{
    public function __construct(private readonly string $configPath) {}

    public function bootstrap(Application $app): void
    {
        $loader = new DirectoryLoader($this->configPath);
        $repository = new Repository();
        $repository->load($loader);

        $app->container()->instance('config', $repository);
    }
}

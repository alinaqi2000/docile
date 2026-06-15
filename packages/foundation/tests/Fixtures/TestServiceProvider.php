<?php

declare(strict_types=1);

namespace Docile\Foundation\Tests\Fixtures;

use Docile\Container\ContainerInterface;
use Docile\Foundation\AbstractServiceProvider;

/**
 * Simple provider that binds 'test' => 'value' in the container.
 */
final class TestServiceProvider extends AbstractServiceProvider
{
    public bool $registered = false;
    public bool $booted = false;

    public function register(ContainerInterface $container): void
    {
        $this->registered = true;
        $container->bind('test', static fn (): string => 'value');
    }

    public function boot(ContainerInterface $container): void
    {
        $this->booted = true;
    }
}

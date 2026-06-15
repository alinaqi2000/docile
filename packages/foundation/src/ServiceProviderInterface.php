<?php

declare(strict_types=1);

namespace Docile\Foundation;

use Docile\Container\ContainerInterface;

interface ServiceProviderInterface
{
    /**
     * Called first — register bindings into the container.
     */
    public function register(ContainerInterface $container): void;

    /**
     * Called after all providers are registered — can resolve from container.
     */
    public function boot(ContainerInterface $container): void;
}

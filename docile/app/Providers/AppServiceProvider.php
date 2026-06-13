<?php

declare(strict_types=1);

namespace App\Providers;

use Docile\Container\ContainerInterface;
use Docile\Foundation\AbstractServiceProvider;

final class AppServiceProvider extends AbstractServiceProvider
{
    /**
     * Register application services into the container.
     */
    public function register(ContainerInterface $container): void
    {
        // Bind your own services here, e.g.:
        // $container->bind(SomeInterface::class, SomeImplementation::class);
    }

    /**
     * Bootstrap any application services.
     *
     * Called after all providers have been registered.
     */
    public function boot(ContainerInterface $container): void
    {
        //
    }
}

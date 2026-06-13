<?php

declare(strict_types=1);

namespace App\Providers;

use Docile\Foundation\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services into the container.
     */
    public function register(): void
    {
        // Bind your own services here, e.g.:
        // $this->app->bind(SomeInterface::class, SomeImplementation::class);
    }

    /**
     * Bootstrap any application services.
     *
     * Called after all providers have been registered.
     */
    public function boot(): void
    {
        //
    }
}

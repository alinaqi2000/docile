<?php

declare(strict_types=1);

namespace App\Providers;

use Docile\Container\ContainerInterface;
use Docile\Foundation\AbstractServiceProvider;
use Docile\Routing\Router;

final class RouteServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $router = new Router();
        $container->instance(Router::class, $router);
    }

    public function boot(ContainerInterface $container): void
    {
        $router = $container->get(Router::class);

        $this->loadRoutes($router, dirname(__DIR__, 2) . '/routes/web.php');
        $this->loadRoutes($router, dirname(__DIR__, 2) . '/routes/api.php');
    }

    private function loadRoutes(Router $router, string $path): void
    {
        $fn = require $path;
        if (is_callable($fn)) {
            $fn($router);
        }
    }
}

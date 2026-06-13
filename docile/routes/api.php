<?php

declare(strict_types=1);

use Docile\Routing\Router;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Register your API routes here. These routes are loaded by the HTTP Kernel
| and will have the api middleware group applied (stateless, JSON responses).
|
| Example:
|
|   $router->get('/users', [App\Http\Controllers\UserController::class, 'index']);
|   $router->post('/users', [App\Http\Controllers\UserController::class, 'store']);
|
*/

/** @var Router $router */

$router->get('/ping', static function (): array {
    return ['status' => 'ok'];
});

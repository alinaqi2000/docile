<?php

declare(strict_types=1);

use Docile\Routing\Router;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Register your web routes here. These routes are loaded by the HTTP Kernel
| and will have the web middleware group applied automatically.
|
| Example:
|
|   $router->get('/', [App\Http\Controllers\HomeController::class, 'index']);
|   $router->post('/contact', [App\Http\Controllers\ContactController::class, 'store']);
|
*/

/** @var Router $router */

$router->get('/', static function (): string {
    return 'Welcome to Docile!';
});

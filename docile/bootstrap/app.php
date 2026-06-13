<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Docile\Container\Container;
use Docile\Foundation\Application;
use Docile\Routing\Router;

$container = new Container();
$app = new Application($container);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| The providers listed in composer.json under extra.docile.providers are
| resolved and registered automatically by the Application. You may also
| register additional providers here before returning the instance.
|
*/
$composerJson = json_decode(
    file_get_contents(dirname(__DIR__) . '/composer.json'),
    true,
    512,
    JSON_THROW_ON_ERROR,
);

foreach ($composerJson['extra']['docile']['providers'] ?? [] as $provider) {
    $app->register($provider);
}

/*
|--------------------------------------------------------------------------
| Bind Router
|--------------------------------------------------------------------------
|
| The router is used by the HTTP Kernel to match incoming requests.
| We bind it here so it can be injected into the Kernel.
|
*/
$router = new Router();
require dirname(__DIR__) . '/routes/web.php';
require dirname(__DIR__) . '/routes/api.php';

$app->container()->instance(Router::class, $router);

return $app;

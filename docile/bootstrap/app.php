<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Docile\Container\Container;
use Docile\Foundation\Application;

$container = new Container();
$app = new Application($container);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Load service providers from config/providers.php
|
*/
$providers = require dirname(__DIR__) . '/config/providers.php';

foreach ($providers as $provider) {
    $app->register($provider);
}

return $app;

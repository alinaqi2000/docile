<?php

declare(strict_types=1);

use Docile\Http\SapiEmitter;
use Docile\Routing\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

$app = require dirname(__DIR__) . '/bootstrap/app.php';

$app->container()->instance(\App\Http\Kernel::class, new \App\Http\Kernel(
    $app->container()->get(Router::class),
    $app->container(),
));

$psr17Factory = new Psr17Factory();
$creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
$request = $creator->fromGlobals();

$response = $app->handleHttp($request, \App\Http\Kernel::class);

(new SapiEmitter())->emit($response);

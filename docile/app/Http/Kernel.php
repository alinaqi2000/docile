<?php

declare(strict_types=1);

namespace App\Http;

use Docile\Http\JsonResponse;
use Docile\Routing\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Kernel implements RequestHandlerInterface
{
    public function __construct(
        private readonly Router $router,
        private readonly ContainerInterface $container,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->router->dispatch($request);

        $handler = $result->definition->handler;

        if (is_callable($handler)) {
            $response = $handler($request);

            return $response;
        }

        return JsonResponse::make(['error' => 'Handler not callable'], 500);
    }
}

<?php

declare(strict_types=1);

namespace Docile\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Kernel implements RequestHandlerInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly MiddlewareQueue $middleware,
        private readonly RequestHandlerInterface $router,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dispatcher = new MiddlewareDispatcher(
            $this->middleware,
            $this->router,
            $this->container
        );

        return $dispatcher->handle($request);
    }
}
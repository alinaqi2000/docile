<?php

declare(strict_types=1);

namespace Docile\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareDispatcher implements RequestHandlerInterface
{
    private int $currentIndex = 0;

    public function __construct(
        private readonly MiddlewareQueue $queue,
        private readonly RequestHandlerInterface $fallback,
        private readonly ContainerInterface $container,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->currentIndex >= count($this->queue->all())) {
            return $this->fallback->handle($request);
        }

        $middleware = $this->queue->all()[$this->currentIndex];
        $this->currentIndex++;

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $this);
        }

        // Resolve middleware class name from container
        if (!is_string($middleware)) {
            throw new \RuntimeException('Middleware must be a string or MiddlewareInterface instance');
        }

        $resolvedMiddleware = $this->container->has($middleware)
            ? $this->container->get($middleware)
            : new $middleware();

        if (!$resolvedMiddleware instanceof MiddlewareInterface) {
            throw new \RuntimeException(sprintf(
                'Resolved middleware "%s" does not implement MiddlewareInterface',
                $middleware
            ));
        }

        return $resolvedMiddleware->process($request, $this);
    }
}
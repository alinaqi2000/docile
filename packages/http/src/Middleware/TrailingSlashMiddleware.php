<?php

declare(strict_types=1);

namespace Docile\Http\Middleware;

use Docile\Http\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TrailingSlashMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        // Check if path ends with / and is not just "/"
        if ($path !== '/' && str_ends_with($path, '/')) {
            $newPath = rtrim($path, '/');
            $newUri = $uri->withPath($newPath === '' ? '/' : $newPath);
            
            return RedirectResponse::make((string) $newUri, 301);
        }

        return $handler->handle($request);
    }
}
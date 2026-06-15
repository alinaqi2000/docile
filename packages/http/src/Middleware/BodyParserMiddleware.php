<?php

declare(strict_types=1);

namespace Docile\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class BodyParserMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');
        
        if (!str_starts_with($contentType, 'application/json')) {
            return $handler->handle($request);
        }

        $body = (string) $request->getBody();
        
        if ($body === '') {
            return $handler->handle($request);
        }

        try {
            $parsedBody = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            // Let the application handle invalid JSON
            return $handler->handle($request);
        }

        // Ensure parsed body is array, object, or null for PSR-7 compatibility
        if (!is_array($parsedBody) && !is_object($parsedBody) && $parsedBody !== null) {
            return $handler->handle($request);
        }

        return $handler->handle($request->withParsedBody($parsedBody));
    }
}
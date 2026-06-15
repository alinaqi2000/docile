<?php

declare(strict_types=1);

namespace Docile\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MethodOverrideMiddleware implements MiddlewareInterface
{
    private const ALLOWED_OVERRIDE_METHODS = ['GET', 'PUT', 'PATCH', 'DELETE'];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return $handler->handle($request);
        }

        $overrideMethod = null;

        // Check X-HTTP-Method-Override header first
        if ($request->hasHeader('X-HTTP-Method-Override')) {
            $overrideMethod = $request->getHeaderLine('X-HTTP-Method-Override');
        }
        // Then check _method in parsed body
        else {
            $parsedBody = $request->getParsedBody();
            if (is_array($parsedBody) && isset($parsedBody['_method']) && \is_string($parsedBody['_method'])) {
                $overrideMethod = $parsedBody['_method'];
            }
        }

        if ($overrideMethod !== null) {
            $overrideMethod = strtoupper($overrideMethod);
            
            if (in_array($overrideMethod, self::ALLOWED_OVERRIDE_METHODS, true)) {
                $request = $request->withMethod($overrideMethod);
            }
        }

        return $handler->handle($request);
    }
}
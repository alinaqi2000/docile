<?php

declare(strict_types=1);

namespace Docile\Testing\Tests\Fixtures;

use Docile\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SimpleKernel implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $uri = (string) $request->getUri();

        return JsonResponse::make([
            'method' => $method,
            'uri' => $uri,
            'status' => 'ok',
        ]);
    }
}

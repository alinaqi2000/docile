<?php

declare(strict_types=1);

namespace Docile\Http;

use Nyholm\Psr7\Response as NyholmResponse;
use Psr\Http\Message\ResponseInterface;

final class Response
{
    /** @param array<string, string|list<string>> $headers */
    public static function make(int $status = 200, array $headers = [], string $body = ''): ResponseInterface
    {
        return new NyholmResponse($status, $headers, $body);
    }
}
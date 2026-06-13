<?php

declare(strict_types=1);

namespace Docile\Http;

use Nyholm\Psr7\Response as NyholmResponse;
use Psr\Http\Message\ResponseInterface;

final class EmptyResponse
{
    /** @param array<string, string|list<string>> $headers */
    public static function make(int $status = 204, array $headers = []): ResponseInterface
    {
        return new NyholmResponse($status, $headers);
    }
}
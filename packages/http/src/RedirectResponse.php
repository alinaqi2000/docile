<?php

declare(strict_types=1);

namespace Docile\Http;

use Nyholm\Psr7\Response as NyholmResponse;
use Psr\Http\Message\ResponseInterface;

final class RedirectResponse
{
    /** @param array<string, string|list<string>> $headers */
    public static function make(string $location, int $status = 302, array $headers = []): ResponseInterface
    {
        $headers = array_merge($headers, ['Location' => [$location]]);
        
        return new NyholmResponse($status, $headers);
    }
}
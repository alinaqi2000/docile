<?php

declare(strict_types=1);

namespace Docile\Http;

use Nyholm\Psr7\Response as NyholmResponse;
use Psr\Http\Message\ResponseInterface;

final class HtmlResponse
{
    /** @param array<string, string|list<string>> $headers */
    public static function make(string $html, int $status = 200, array $headers = []): ResponseInterface
    {
        $headers = array_merge($headers, ['Content-Type' => ['text/html; charset=UTF-8']]);
        
        return new NyholmResponse($status, $headers, $html);
    }
}
<?php

declare(strict_types=1);

namespace Docile\Http;

use Nyholm\Psr7\Response as NyholmResponse;
use Psr\Http\Message\ResponseInterface;

final class JsonResponse
{
    /** @param array<string, string|list<string>> $headers */
    public static function make(mixed $data, int $status = 200, array $headers = []): ResponseInterface
    {
        $headers = array_merge($headers, ['Content-Type' => ['application/json']]);
        
        try {
            $body = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Failed to encode JSON data: ' . $e->getMessage(), 0, $e);
        }
        
        return new NyholmResponse($status, $headers, $body);
    }
}
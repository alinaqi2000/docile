<?php

declare(strict_types=1);

namespace Docile\Http;

use Nyholm\Psr7\Response as NyholmResponse;
use Psr\Http\Message\ResponseInterface;

final class ProblemResponse
{
    /**
     * @param array<string, mixed>              $extra
     * @param array<string, string|list<string>> $headers
     */
    public static function make(
        string $title,
        int $status = 500,
        string $detail = '',
        string $type = 'about:blank',
        array $extra = [],
        array $headers = []
    ): ResponseInterface {
        $problem = array_merge([
            'type' => $type,
            'title' => $title,
            'status' => $status,
        ], $extra);
        
        if ($detail !== '') {
            $problem['detail'] = $detail;
        }
        
        $headers = array_merge($headers, ['Content-Type' => ['application/problem+json']]);
        
        try {
            $body = json_encode($problem, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Failed to encode problem details JSON: ' . $e->getMessage(), 0, $e);
        }
        
        return new NyholmResponse($status, $headers, $body);
    }
}
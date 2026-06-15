<?php

declare(strict_types=1);

namespace Docile\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class JsonResponseTest extends TestCase
{
    public function testMakeWithDefaults(): void
    {
        $data = ['message' => 'Hello'];
        $response = \Docile\Http\JsonResponse::make($data);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(json_encode($data), (string) $response->getBody());
    }

    public function testMakeWithCustomStatus(): void
    {
        $data = ['error' => 'Not found'];
        $response = \Docile\Http\JsonResponse::make($data, 404);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testMakeWithHeaders(): void
    {
        $data = ['test' => true];
        $headers = ['X-Custom' => ['value']];
        $response = \Docile\Http\JsonResponse::make($data, 200, $headers);

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(['value'], $response->getHeader('X-Custom'));
    }

    public function testMakeMergesContentType(): void
    {
        $data = ['test' => true];
        $headers = ['Content-Type' => ['text/plain']];
        $response = \Docile\Http\JsonResponse::make($data, 200, $headers);

        // Our implementation should override existing Content-Type
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testMakeWithComplexData(): void
    {
        $data = [
            'user' => [
                'id' => 123,
                'name' => 'John Doe',
                'active' => true,
                'roles' => ['admin', 'user'],
            ],
            'meta' => null,
        ];

        $response = \Docile\Http\JsonResponse::make($data);
        $this->assertSame(json_encode($data), (string) $response->getBody());
    }

    public function testMakeWithInvalidDataThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode JSON data');

        // Create a resource that cannot be JSON encoded
        $resource = fopen('php://memory', 'r');
        \Docile\Http\JsonResponse::make($resource);
        fclose($resource);
    }
}
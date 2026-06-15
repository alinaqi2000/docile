<?php

declare(strict_types=1);

namespace Docile\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ProblemResponseTest extends TestCase
{
    public function testMakeWithMinimumParameters(): void
    {
        $title = 'Error occurred';
        $response = \Docile\Http\ProblemResponse::make($title);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('about:blank', $body['type']);
        $this->assertSame($title, $body['title']);
        $this->assertSame(500, $body['status']);
        $this->assertArrayNotHasKey('detail', $body);
    }

    public function testMakeWithAllParameters(): void
    {
        $title = 'Validation failed';
        $status = 422;
        $detail = 'The request data is invalid';
        $type = 'https://example.com/problems/validation';
        $extra = ['field' => 'email', 'message' => 'Invalid format'];

        $response = \Docile\Http\ProblemResponse::make($title, $status, $detail, $type, $extra);

        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame($type, $body['type']);
        $this->assertSame($title, $body['title']);
        $this->assertSame($status, $body['status']);
        $this->assertSame($detail, $body['detail']);
        $this->assertSame('email', $body['field']);
        $this->assertSame('Invalid format', $body['message']);
    }

    public function testMakeWithEmptyDetail(): void
    {
        $title = 'Error';
        $response = \Docile\Http\ProblemResponse::make($title, 400, '');

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayNotHasKey('detail', $body);
    }

    public function testMakeWithCustomType(): void
    {
        $title = 'Not found';
        $type = 'https://example.com/problems/not-found';
        $response = \Docile\Http\ProblemResponse::make($title, 404, 'Resource not found', $type);

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame($type, $body['type']);
    }

    public function testMakeWithHeaders(): void
    {
        $title = 'Error';
        $headers = ['X-Custom' => ['value']];
        $response = \Docile\Http\ProblemResponse::make($title, 500, 'Detail', 'about:blank', [], $headers);

        $this->assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(['value'], $response->getHeader('X-Custom'));
    }

    public function testMakeMergesContentType(): void
    {
        $title = 'Error';
        $headers = ['Content-Type' => ['text/plain']];
        $response = \Docile\Http\ProblemResponse::make($title, 500, 'Detail', 'about:blank', [], $headers);

        $this->assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));
    }

    public function testMakeWithComplexExtraData(): void
    {
        $title = 'Validation error';
        $extra = [
            'violations' => [
                ['field' => 'email', 'message' => 'Invalid email'],
                ['field' => 'age', 'message' => 'Must be positive'],
            ],
            'request_id' => '12345',
        ];

        $response = \Docile\Http\ProblemResponse::make($title, 422, 'Validation failed', 'about:blank', $extra);

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame($extra['violations'], $body['violations']);
        $this->assertSame($extra['request_id'], $body['request_id']);
    }

    public function testMakeWithInvalidJsonDataThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode problem details JSON');

        // Create a resource that cannot be JSON encoded
        $resource = fopen('php://memory', 'r');
        \Docile\Http\ProblemResponse::make('Error', 500, 'Detail', 'about:blank', [$resource]);
        fclose($resource);
    }
}
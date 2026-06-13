<?php

declare(strict_types=1);

namespace Docile\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ResponseTest extends TestCase
{
    public function testMakeWithDefaults(): void
    {
        $response = \Docile\Http\Response::make();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
        $this->assertSame([], $response->getHeaders());
    }

    public function testMakeWithCustomStatus(): void
    {
        $response = \Docile\Http\Response::make(404);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testMakeWithHeaders(): void
    {
        $headers = ['X-Custom' => ['value1', 'value2']];
        $response = \Docile\Http\Response::make(200, $headers);

        $this->assertSame(['value1', 'value2'], $response->getHeader('X-Custom'));
    }

    public function testMakeWithBody(): void
    {
        $body = 'Hello, World!';
        $response = \Docile\Http\Response::make(200, [], $body);

        $this->assertSame($body, (string) $response->getBody());
    }

    public function testMakeWithAllParameters(): void
    {
        $status = 201;
        $headers = ['Location' => ['/new-resource']];
        $body = 'Resource created';

        $response = \Docile\Http\Response::make($status, $headers, $body);

        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame(['/new-resource'], $response->getHeader('Location'));
        $this->assertSame($body, (string) $response->getBody());
    }
}
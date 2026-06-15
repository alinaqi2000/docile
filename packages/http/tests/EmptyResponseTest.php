<?php

declare(strict_types=1);

namespace Docile\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class EmptyResponseTest extends TestCase
{
    public function testMakeWithDefaults(): void
    {
        $response = \Docile\Http\EmptyResponse::make();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
        $this->assertSame([], $response->getHeaders());
    }

    public function testMakeWithCustomStatus(): void
    {
        $response = \Docile\Http\EmptyResponse::make(205);

        $this->assertSame(205, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
    }

    public function testMakeWithHeaders(): void
    {
        $headers = ['X-Custom' => ['value']];
        $response = \Docile\Http\EmptyResponse::make(204, $headers);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
        $this->assertSame(['value'], $response->getHeader('X-Custom'));
    }

    public function testMakeCommonStatusCodes(): void
    {
        $response204 = \Docile\Http\EmptyResponse::make(204);
        $this->assertSame(204, $response204->getStatusCode());

        $response205 = \Docile\Http\EmptyResponse::make(205);
        $this->assertSame(205, $response205->getStatusCode());

        $response304 = \Docile\Http\EmptyResponse::make(304);
        $this->assertSame(304, $response304->getStatusCode());
    }

    public function testMakeAlwaysHasEmptyBody(): void
    {
        $response = \Docile\Http\EmptyResponse::make(200, ['Content-Length' => ['100']]);
        
        $this->assertSame('', (string) $response->getBody());
        // Content-Length header should still be preserved if set
        $this->assertSame(['100'], $response->getHeader('Content-Length'));
    }
}
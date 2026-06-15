<?php

declare(strict_types=1);

namespace Docile\Http\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class HtmlResponseTest extends TestCase
{
    public function testMakeWithDefaults(): void
    {
        $html = '<h1>Hello, World!</h1>';
        $response = \Docile\Http\HtmlResponse::make($html);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame($html, (string) $response->getBody());
    }

    public function testMakeWithCustomStatus(): void
    {
        $html = '<h1>Error</h1>';
        $response = \Docile\Http\HtmlResponse::make($html, 404);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame($html, (string) $response->getBody());
    }

    public function testMakeWithHeaders(): void
    {
        $html = '<p>Content</p>';
        $headers = ['X-Custom' => ['value']];
        $response = \Docile\Http\HtmlResponse::make($html, 200, $headers);

        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame(['value'], $response->getHeader('X-Custom'));
        $this->assertSame($html, (string) $response->getBody());
    }

    public function testMakeMergesContentType(): void
    {
        $html = '<p>Test</p>';
        $headers = ['Content-Type' => ['text/plain']];
        $response = \Docile\Http\HtmlResponse::make($html, 200, $headers);

        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testMakeWithEmptyHtml(): void
    {
        $response = \Docile\Http\HtmlResponse::make('');

        $this->assertSame('', (string) $response->getBody());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testMakeWithComplexHtml(): void
    {
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body><div class="container">Content</div></body></html>';
        $response = \Docile\Http\HtmlResponse::make($html);

        $this->assertSame($html, (string) $response->getBody());
    }
}
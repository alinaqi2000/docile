<?php

declare(strict_types=1);

namespace Docile\Http\Tests\Middleware;

use Docile\Http\Middleware\SecurityHeadersMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SecurityHeadersMiddlewareTest extends TestCase
{
    private SecurityHeadersMiddleware $middleware;
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->middleware = new SecurityHeadersMiddleware();
        $this->request = new ServerRequest('GET', '/test');
    }

    public function testProcessAddsSecurityHeaders(): void
    {
        $originalResponse = new Response(200, ['Content-Type' => 'text/plain'], 'Hello');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($originalResponse);

        $response = $this->middleware->process($this->request, $handler);

        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('1; mode=block', $response->getHeaderLine('X-XSS-Protection'));
        $this->assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
        $this->assertSame('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertSame('Hello', (string) $response->getBody());
    }

    public function testProcessDoesNotOverrideExistingHeaders(): void
    {
        $existingHeaders = [
            'X-Content-Type-Options' => ['existing-value'],
            'X-Frame-Options' => ['SAMEORIGIN'],
            'X-XSS-Protection' => ['0'],
            'Referrer-Policy' => ['no-referrer'],
        ];
        $originalResponse = new Response(200, $existingHeaders, 'Hello');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($originalResponse);

        $response = $this->middleware->process($this->request, $handler);

        $this->assertSame('existing-value', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('0', $response->getHeaderLine('X-XSS-Protection'));
        $this->assertSame('no-referrer', $response->getHeaderLine('Referrer-Policy'));
    }

    public function testProcessAddsMissingHeadersOnly(): void
    {
        $existingHeaders = [
            'X-Content-Type-Options' => ['nosniff'], // Already correct
            // X-Frame-Options is missing
            'X-XSS-Protection' => ['0'], // Different value
            // Referrer-Policy is missing
        ];
        $originalResponse = new Response(200, $existingHeaders, 'Hello');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($originalResponse);

        $response = $this->middleware->process($this->request, $handler);

        // Should not change existing header
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        // Should add missing header
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        // Should not change existing header
        $this->assertSame('0', $response->getHeaderLine('X-XSS-Protection'));
        // Should add missing header
        $this->assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
    }

    public function testProcessWithEmptyResponse(): void
    {
        $originalResponse = new Response(204);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($originalResponse);

        $response = $this->middleware->process($this->request, $handler);

        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('1; mode=block', $response->getHeaderLine('X-XSS-Protection'));
        $this->assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testProcessWithErrorResponse(): void
    {
        $originalResponse = new Response(404, [], 'Not Found');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($originalResponse);

        $response = $this->middleware->process($this->request, $handler);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', (string) $response->getBody());
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
    }

    public function testProcessPreservesOtherHeaders(): void
    {
        $originalResponse = new Response(200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache',
            'X-Custom' => 'custom-value',
        ], '{"test": true}');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($originalResponse);

        $response = $this->middleware->process($this->request, $handler);

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('no-cache', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('custom-value', $response->getHeaderLine('X-Custom'));
        $this->assertSame('{"test": true}', (string) $response->getBody());
    }

    public function testProcessWithMultipleValuesForSecurityHeaders(): void
    {
        $originalResponse = new Response(200, [
            'X-Content-Type-Options' => ['value1', 'nosniff'], // Has nosniff in values
        ], 'Hello');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($originalResponse);

        $response = $this->middleware->process($this->request, $handler);

        // Should not add if header exists (even with multiple values)
        $this->assertSame(['value1', 'nosniff'], $response->getHeader('X-Content-Type-Options'));
        // Should add other missing headers
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
    }

    public function testProcessDoesNotModifyRequest(): void
    {
        $originalResponse = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($this->request))
            ->willReturn($originalResponse);

        $this->middleware->process($this->request, $handler);
    }

    public function testProcessReturnsNewResponseInstance(): void
    {
        $originalResponse = new Response(200, [], 'Hello');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturn($originalResponse);

        $response = $this->middleware->process($this->request, $handler);

        $this->assertNotSame($originalResponse, $response);
        $this->assertSame('Hello', (string) $response->getBody());
    }
}
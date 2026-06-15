<?php

declare(strict_types=1);

namespace Docile\Security\Tests\RateLimit;

use Docile\Security\Exception\TooManyRequestsException;
use Docile\Security\RateLimit\RateLimitMiddleware;
use Docile\Security\RateLimit\RateLimiterInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(RateLimitMiddleware::class)]
final class RateLimitMiddlewareTest extends TestCase
{
    private RateLimitMiddleware $middleware;
    private RateLimiterInterface $limiter;
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->limiter = $this->createMock(RateLimiterInterface::class);
        $this->middleware = new RateLimitMiddleware($this->limiter, 60, 60);
        $this->factory = new Psr17Factory();
    }

    public function testProcessAddsRateLimitHeaders(): void
    {
        $this->limiter->method('attempt')->willReturn(true);
        $this->limiter->method('remaining')->willReturn(59);

        $request = $this->factory->createServerRequest('GET', '/test', ['REMOTE_ADDR' => '127.0.0.1']);

        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame('60', $response->getHeaderLine('X-RateLimit-Limit'));
        $this->assertSame('59', $response->getHeaderLine('X-RateLimit-Remaining'));
    }

    public function testProcessThrowsExceptionWhenLimitExceeded(): void
    {
        $this->limiter->method('attempt')->willReturn(false);

        $request = $this->factory->createServerRequest('GET', '/test', ['REMOTE_ADDR' => '127.0.0.1']);

        $handler = $this->createMockHandler(new Response());

        $this->expectException(TooManyRequestsException::class);

        $this->middleware->process($request, $handler);
    }

    public function testProcessUsesRemoteAddrForKey(): void
    {
        $this->limiter->expects($this->once())
            ->method('attempt')
            ->with('rate_limit:127.0.0.1', 60, 60)
            ->willReturn(true);

        $this->limiter->method('remaining')->willReturn(59);

        $request = $this->factory->createServerRequest('GET', '/test', ['REMOTE_ADDR' => '127.0.0.1']);

        $handler = $this->createMockHandler(new Response());

        $this->middleware->process($request, $handler);
    }

    public function testProcessUsesUnknownWhenRemoteAddrMissing(): void
    {
        $this->limiter->expects($this->once())
            ->method('attempt')
            ->with('rate_limit:unknown', 60, 60)
            ->willReturn(true);

        $this->limiter->method('remaining')->willReturn(59);

        $request = $this->factory->createServerRequest('GET', '/test');

        $handler = $this->createMockHandler(new Response());

        $this->middleware->process($request, $handler);
    }

    public function testProcessCallsRemainingWithCorrectKey(): void
    {
        $this->limiter->method('attempt')->willReturn(true);
        $this->limiter->expects($this->once())
            ->method('remaining')
            ->with('rate_limit:127.0.0.1', 60)
            ->willReturn(59);

        $request = $this->factory->createServerRequest('GET', '/test', ['REMOTE_ADDR' => '127.0.0.1']);

        $handler = $this->createMockHandler(new Response());

        $this->middleware->process($request, $handler);
    }

    public function testProcessUsesCustomMaxAttempts(): void
    {
        $middleware = new RateLimitMiddleware($this->limiter, 100, 60);

        $this->limiter->method('attempt')->willReturn(true);
        $this->limiter->method('remaining')->willReturn(99);

        $request = $this->factory->createServerRequest('GET', '/test', ['REMOTE_ADDR' => '127.0.0.1']);

        $handler = $this->createMockHandler(new Response());

        $response = $middleware->process($request, $handler);

        $this->assertSame('100', $response->getHeaderLine('X-RateLimit-Limit'));
    }

    public function testProcessUsesCustomDecaySeconds(): void
    {
        $middleware = new RateLimitMiddleware($this->limiter, 60, 120);

        $this->limiter->expects($this->once())
            ->method('attempt')
            ->with('rate_limit:127.0.0.1', 60, 120)
            ->willReturn(true);

        $this->limiter->method('remaining')->willReturn(59);

        $request = $this->factory->createServerRequest('GET', '/test', ['REMOTE_ADDR' => '127.0.0.1']);

        $handler = $this->createMockHandler(new Response());

        $middleware->process($request, $handler);
    }

    public function testProcessReturnsResponseFromHandler(): void
    {
        $this->limiter->method('attempt')->willReturn(true);
        $this->limiter->method('remaining')->willReturn(59);

        $request = $this->factory->createServerRequest('GET', '/test', ['REMOTE_ADDR' => '127.0.0.1']);

        $handlerResponse = new Response(201);
        $handler = $this->createMockHandler($handlerResponse);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testProcessAddsHeadersToExistingResponse(): void
    {
        $this->limiter->method('attempt')->willReturn(true);
        $this->limiter->method('remaining')->willReturn(59);

        $request = $this->factory->createServerRequest('GET', '/test', ['REMOTE_ADDR' => '127.0.0.1']);

        $handlerResponse = new Response(200, ['X-Custom' => 'value']);
        $handler = $this->createMockHandler($handlerResponse);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame('value', $response->getHeaderLine('X-Custom'));
        $this->assertSame('60', $response->getHeaderLine('X-RateLimit-Limit'));
        $this->assertSame('59', $response->getHeaderLine('X-RateLimit-Remaining'));
    }

    private function createMockHandler(Response $response): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        return $handler;
    }
}

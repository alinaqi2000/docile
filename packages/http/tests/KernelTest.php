<?php

declare(strict_types=1);

namespace Docile\Http\Tests;

use Docile\Http\Kernel;
use Docile\Http\MiddlewareDispatcher;
use Docile\Http\MiddlewareQueue;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class KernelTest extends TestCase
{
    private ServerRequestInterface $request;
    private ResponseInterface $expectedResponse;

    protected function setUp(): void
    {
        $this->request = new ServerRequest('GET', '/test');
        $this->expectedResponse = new Response(200, [], 'Kernel response');
    }

    public function testHandleCreatesDispatcherAndDelegates(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $middleware = new MiddlewareQueue();
        $router = $this->createMock(RequestHandlerInterface::class);

        $router->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->expectedResponse);

        $kernel = new Kernel($container, $middleware, $router);
        $response = $kernel->handle($this->request);

        $this->assertSame($this->expectedResponse, $response);
    }

    public function testHandleWithMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $router = $this->createMock(RequestHandlerInterface::class);
        $router->expects($this->once())
            ->method('handle')
            ->willReturn($this->expectedResponse);

        // Create a test middleware that adds a header
        $middleware = new MiddlewareQueue([TestKernelMiddleware::class]);

        $kernel = new Kernel($container, $middleware, $router);
        $response = $kernel->handle($this->request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Kernel response', (string) $response->getBody());
        $this->assertSame('test-value', $response->getHeaderLine('X-Test'));
    }

    public function testHandleWithMultipleMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $router = $this->createMock(RequestHandlerInterface::class);
        $router->expects($this->once())
            ->method('handle')
            ->willReturn($this->expectedResponse);

        $middleware = new MiddlewareQueue([
            TestKernelMiddleware::class,
            AnotherTestKernelMiddleware::class,
        ]);

        $kernel = new Kernel($container, $middleware, $router);
        $response = $kernel->handle($this->request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('test-value', $response->getHeaderLine('X-Test'));
        $this->assertSame('another-value', $response->getHeaderLine('X-Another'));
    }

    public function testKernelImplementsRequestHandlerInterface(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $middleware = new MiddlewareQueue();
        $router = $this->createMock(RequestHandlerInterface::class);

        $kernel = new Kernel($container, $middleware, $router);

        $this->assertInstanceOf(RequestHandlerInterface::class, $kernel);
    }

    public function testHandlePassesCorrectDependenciesToDispatcher(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $middleware = new MiddlewareQueue(['TestMiddleware']);
        $router = $this->createMock(RequestHandlerInterface::class);
        $router->expects($this->once())
            ->method('handle')
            ->willReturn($this->expectedResponse);

        // Test that the container is used for middleware resolution
        $testMiddleware = $this->createMock(\Psr\Http\Server\MiddlewareInterface::class);
        $testMiddleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                return $handler->handle($request);
            });

        $container->expects($this->once())
            ->method('has')
            ->with('TestMiddleware')
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with('TestMiddleware')
            ->willReturn($testMiddleware);

        $kernel = new Kernel($container, $middleware, $router);
        $response = $kernel->handle($this->request);

        $this->assertSame($this->expectedResponse, $response);
    }

    public function testHandleWithEmptyMiddlewareQueue(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $middleware = new MiddlewareQueue();
        $router = $this->createMock(RequestHandlerInterface::class);

        $router->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->expectedResponse);

        $kernel = new Kernel($container, $middleware, $router);
        $response = $kernel->handle($this->request);

        $this->assertSame($this->expectedResponse, $response);
    }
}

// Test middleware classes for kernel tests
class TestKernelMiddleware implements \Psr\Http\Server\MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        return $response->withHeader('X-Test', 'test-value');
    }
}

class AnotherTestKernelMiddleware implements \Psr\Http\Server\MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        return $response->withHeader('X-Another', 'another-value');
    }
}
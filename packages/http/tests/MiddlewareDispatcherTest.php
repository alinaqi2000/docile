<?php

declare(strict_types=1);

namespace Docile\Http\Tests;

use Docile\Http\MiddlewareDispatcher;
use Docile\Http\MiddlewareQueue;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareDispatcherTest extends TestCase
{
    private ServerRequestInterface $request;
    private ResponseInterface $fallbackResponse;

    protected function setUp(): void
    {
        $this->request = new ServerRequest('GET', '/test');
        $this->fallbackResponse = new Response(200, [], 'Fallback');
    }

    public function testHandleWithEmptyQueueDelegatesToFallback(): void
    {
        $fallback = $this->createMock(RequestHandlerInterface::class);
        $fallback->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->fallbackResponse);

        $container = $this->createMock(ContainerInterface::class);
        $queue = new MiddlewareQueue();

        $dispatcher = new MiddlewareDispatcher($queue, $fallback, $container);
        $response = $dispatcher->handle($this->request);

        $this->assertSame($this->fallbackResponse, $response);
    }

    public function testHandleWithSingleMiddlewareObject(): void
    {
        $middlewareResponse = new Response(201, [], 'Middleware');

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(RequestHandlerInterface::class))
            ->willReturn($middlewareResponse);

        $fallback = $this->createMock(RequestHandlerInterface::class);
        $fallback->expects($this->never())->method('handle');

        $container = $this->createMock(ContainerInterface::class);
        $queue = new MiddlewareQueue([$middleware]);

        $dispatcher = new MiddlewareDispatcher($queue, $fallback, $container);
        $response = $dispatcher->handle($this->request);

        $this->assertSame($middlewareResponse, $response);
    }

    public function testHandleWithMultipleMiddlewareObjects(): void
    {
        $response1 = new Response(201, [], 'First');
        $response2 = new Response(202, [], 'Second');

        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware1->expects($this->once())
            ->method('process')
            ->willReturnCallback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($response1) {
                return $response1;
            });

        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $middleware2->expects($this->never())
            ->method('process');

        $fallback = $this->createMock(RequestHandlerInterface::class);
        $fallback->expects($this->never())->method('handle');

        $container = $this->createMock(ContainerInterface::class);
        $queue = new MiddlewareQueue([$middleware1, $middleware2]);

        $dispatcher = new MiddlewareDispatcher($queue, $fallback, $container);
        $response = $dispatcher->handle($this->request);

        $this->assertSame($response1, $response);
    }

    public function testHandleWithMiddlewareCallingNext(): void
    {
        $middlewareResponse = new Response(201, [], 'Middleware');

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($middlewareResponse) {
                // Modify request and call next
                $modifiedRequest = $request->withHeader('X-Modified', 'true');
                return $handler->handle($modifiedRequest);
            });

        $fallback = $this->createMock(RequestHandlerInterface::class);
        $fallback->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $request) {
                return $request->getHeaderLine('X-Modified') === 'true';
            }))
            ->willReturn($middlewareResponse);

        $container = $this->createMock(ContainerInterface::class);
        $queue = new MiddlewareQueue([$middleware]);

        $dispatcher = new MiddlewareDispatcher($queue, $fallback, $container);
        $response = $dispatcher->handle($this->request);

        $this->assertSame($middlewareResponse, $response);
    }

    public function testHandleWithMiddlewareStringResolvedFromContainer(): void
    {
        $middlewareResponse = new Response(201, [], 'Resolved Middleware');

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturn($middlewareResponse);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('TestMiddleware')
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with('TestMiddleware')
            ->willReturn($middleware);

        $fallback = $this->createMock(RequestHandlerInterface::class);
        $fallback->expects($this->never())->method('handle');

        $queue = new MiddlewareQueue(['TestMiddleware']);

        $dispatcher = new MiddlewareDispatcher($queue, $fallback, $container);
        $response = $dispatcher->handle($this->request);

        $this->assertSame($middlewareResponse, $response);
    }

    public function testHandleWithMiddlewareStringInstantiatedDirectly(): void
    {
        $middlewareResponse = new Response(201, [], 'Instantiated Middleware');

        $fallback = $this->createMock(RequestHandlerInterface::class);
        $fallback->expects($this->never())->method('handle');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('Docile\Http\Tests\TestMiddleware')
            ->willReturn(false);
        $container->expects($this->never())->method('get');

        $queue = new MiddlewareQueue([TestMiddleware::class]);

        $dispatcher = new MiddlewareDispatcher($queue, $fallback, $container);
        $response = $dispatcher->handle($this->request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Instantiated Middleware', (string) $response->getBody());
    }

    public function testHandleWithInvalidMiddlewareTypeThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Middleware must be a string or MiddlewareInterface instance');

        $container = $this->createMock(ContainerInterface::class);
        $queue = new MiddlewareQueue([123]); // Invalid type

        $fallback = $this->createMock(RequestHandlerInterface::class);

        $dispatcher = new MiddlewareDispatcher($queue, $fallback, $container);
        $dispatcher->handle($this->request);
    }

    public function testHandleWithNonMiddlewareClassThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resolved middleware "stdClass" does not implement MiddlewareInterface');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('stdClass')
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with('stdClass')
            ->willReturn(new \stdClass());

        $fallback = $this->createMock(RequestHandlerInterface::class);
        $queue = new MiddlewareQueue(['stdClass']);

        $dispatcher = new MiddlewareDispatcher($queue, $fallback, $container);
        $dispatcher->handle($this->request);
    }

    public function testHandleWithMultipleMiddlewareCallingNext(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware1->expects($this->once())
            ->method('process')
            ->willReturnCallback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                $modifiedRequest = $request->withHeader('X-Middleware1', 'true');
                return $handler->handle($modifiedRequest);
            });

        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $middleware2->expects($this->once())
            ->method('process')
            ->willReturnCallback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                $modifiedRequest = $request->withHeader('X-Middleware2', 'true');
                return $handler->handle($modifiedRequest);
            });

        $fallback = $this->createMock(RequestHandlerInterface::class);
        $fallback->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $request) {
                return $request->getHeaderLine('X-Middleware1') === 'true'
                    && $request->getHeaderLine('X-Middleware2') === 'true';
            }))
            ->willReturn($this->fallbackResponse);

        $container = $this->createMock(ContainerInterface::class);
        $queue = new MiddlewareQueue([$middleware1, $middleware2]);

        $dispatcher = new MiddlewareDispatcher($queue, $fallback, $container);
        $response = $dispatcher->handle($this->request);

        $this->assertSame($this->fallbackResponse, $response);
    }
}

// Test middleware class for direct instantiation tests
class TestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return new Response(201, [], 'Instantiated Middleware');
    }
}
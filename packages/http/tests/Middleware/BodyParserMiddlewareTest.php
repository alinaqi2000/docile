<?php

declare(strict_types=1);

namespace Docile\Http\Tests\Middleware;

use Docile\Http\Middleware\BodyParserMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BodyParserMiddlewareTest extends TestCase
{
    private BodyParserMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new BodyParserMiddleware();
    }

    public function testProcessWithNonJsonContentType(): void
    {
        $contentTypes = [
            'text/plain',
            'application/x-www-form-urlencoded',
            'multipart/form-data',
            'text/html',
            'application/xml',
        ];

        foreach ($contentTypes as $contentType) {
            $request = new ServerRequest('POST', '/test');
            $request = $request->withHeader('Content-Type', $contentType);
            $request = $request->withBody(\Nyholm\Psr7\Stream::create('raw body content'));
            
            $response = new Response(200);
            $handler = $this->createMock(RequestHandlerInterface::class);
            $handler->expects($this->once())
                ->method('handle')
                ->with($this->identicalTo($request))
                ->willReturn($response);

            $result = $this->middleware->process($request, $handler);

            $this->assertSame($response, $result);
        }
    }

    public function testProcessWithJsonContentType(): void
    {
        $jsonData = '{"name": "John", "age": 30}';
        $expectedParsedBody = ['name' => 'John', 'age' => 30];

        $request = new ServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(\Nyholm\Psr7\Stream::create($jsonData));
        
        $response = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $req) use ($expectedParsedBody) {
                return $req->getParsedBody() === $expectedParsedBody;
            }))
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testProcessWithJsonContentTypeWithCharset(): void
    {
        $jsonData = '{"test": true}';
        $expectedParsedBody = ['test' => true];

        $request = new ServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json; charset=utf-8');
        $request = $request->withBody(\Nyholm\Psr7\Stream::create($jsonData));
        
        $response = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $req) use ($expectedParsedBody) {
                return $req->getParsedBody() === $expectedParsedBody;
            }))
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testProcessWithEmptyJsonBody(): void
    {
        $request = new ServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(\Nyholm\Psr7\Stream::create(''));
        
        $response = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testProcessWithInvalidJson(): void
    {
        $invalidJson = '{"invalid": json}';

        $request = new ServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(\Nyholm\Psr7\Stream::create($invalidJson));
        
        $response = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request)) // Should pass through unchanged
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testProcessWithComplexJsonData(): void
    {
        $jsonData = json_encode([
            'user' => [
                'id' => 123,
                'name' => 'John Doe',
                'active' => true,
                'roles' => ['admin', 'user'],
            ],
            'meta' => [
                'timestamp' => '2023-01-01T00:00:00Z',
                'version' => 1.0,
            ],
            'null_value' => null,
        ]);
        $expectedParsedBody = json_decode($jsonData, true);

        $request = new ServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(\Nyholm\Psr7\Stream::create($jsonData));
        
        $response = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $req) use ($expectedParsedBody) {
                return $req->getParsedBody() === $expectedParsedBody;
            }))
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testProcessWithJsonArray(): void
    {
        $jsonData = '[1, 2, 3, {"nested": true}]';
        $expectedParsedBody = [1, 2, 3, ['nested' => true]];

        $request = new ServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(\Nyholm\Psr7\Stream::create($jsonData));
        
        $response = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $req) use ($expectedParsedBody) {
                return $req->getParsedBody() === $expectedParsedBody;
            }))
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testProcessWithJsonNumber(): void
    {
        $jsonData = '42';
        // Numbers are not valid parsed body for PSR-7, should pass through unchanged
        $request = new ServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(\Nyholm\Psr7\Stream::create($jsonData));
        
        $response = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request)) // Should pass through unchanged
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testProcessWithJsonBoolean(): void
    {
        $jsonData = 'true';
        // Booleans are not valid parsed body for PSR-7, should pass through unchanged
        $request = new ServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(\Nyholm\Psr7\Stream::create($jsonData));
        
        $response = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request)) // Should pass through unchanged
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testProcessWithJsonNull(): void
    {
        $jsonData = 'null';
        $expectedParsedBody = null;

        $request = new ServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(\Nyholm\Psr7\Stream::create($jsonData));
        
        $response = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $req) use ($expectedParsedBody) {
                return $req->getParsedBody() === $expectedParsedBody;
            }))
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testProcessWithoutContentTypeHeader(): void
    {
        $request = new ServerRequest('POST', '/test');
        $request = $request->withBody(\Nyholm\Psr7\Stream::create('some body'));
        
        $response = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }

    public function testProcessReturnsModifiedRequestToHandler(): void
    {
        $jsonData = '{"test": "value"}';
        $originalRequest = new ServerRequest('POST', '/test');
        $originalRequest = $originalRequest->withHeader('Content-Type', 'application/json');
        $originalRequest = $originalRequest->withBody(\Nyholm\Psr7\Stream::create($jsonData));
        
        $response = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $req) use ($originalRequest) {
                // Should be a different instance with modified parsed body
                return $req !== $originalRequest 
                    && $req->getParsedBody() === ['test' => 'value']
                    && $req->getHeaderLine('Content-Type') === 'application/json';
            }))
            ->willReturn($response);

        $result = $this->middleware->process($originalRequest, $handler);

        $this->assertSame($response, $result);
        // Original request should be unchanged
        $this->assertNull($originalRequest->getParsedBody());
    }

    public function testProcessWithWhitespaceAroundContentType(): void
    {
        $jsonData = '{"test": true}';
        $expectedParsedBody = ['test' => true];

        $request = new ServerRequest('POST', '/test');
        $request = $request->withHeader('Content-Type', ' application/json ');
        $request = $request->withBody(\Nyholm\Psr7\Stream::create($jsonData));
        
        $response = new Response(200);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (ServerRequestInterface $req) use ($expectedParsedBody) {
                return $req->getParsedBody() === $expectedParsedBody;
            }))
            ->willReturn($response);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($response, $result);
    }
}
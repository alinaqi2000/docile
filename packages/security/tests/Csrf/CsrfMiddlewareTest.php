<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Csrf;

use Docile\Security\Csrf\CsrfMiddleware;
use Docile\Security\Csrf\CsrfTokenManager;
use Docile\Security\Exception\CsrfTokenMismatchException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(CsrfMiddleware::class)]
final class CsrfMiddlewareTest extends TestCase
{
    private CsrfMiddleware $middleware;
    private CsrfTokenManager $manager;
    private Psr17Factory $factory;
    /** @var array<string, mixed> */
    private array $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new CsrfTokenManager();
        $this->middleware = new CsrfMiddleware($this->manager);
        $this->factory = new Psr17Factory();
        $this->session = [];
    }

    public function testProcessAllowsGetRequests(): void
    {
        $request = $this->factory->createServerRequest('GET', '/test');
        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessAllowsHeadRequests(): void
    {
        $request = $this->factory->createServerRequest('HEAD', '/test');
        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessAllowsOptionsRequests(): void
    {
        $request = $this->factory->createServerRequest('OPTIONS', '/test');
        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessValidatesPostRequestWithValidTokenInBody(): void
    {
        $token = $this->manager->generate($this->session);

        $request = $this->factory->createServerRequest('POST', '/test')
            ->withParsedBody(['_token' => $token])
            ->withAttribute('session', $this->session);

        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessValidatesPostRequestWithValidTokenInHeader(): void
    {
        $token = $this->manager->generate($this->session);

        $request = $this->factory->createServerRequest('POST', '/test')
            ->withHeader('X-CSRF-TOKEN', $token)
            ->withAttribute('session', $this->session);

        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessThrowsExceptionForPostRequestWithoutToken(): void
    {
        $request = $this->factory->createServerRequest('POST', '/test')
            ->withAttribute('session', $this->session);

        $handler = $this->createMockHandler(new Response());

        $this->expectException(CsrfTokenMismatchException::class);

        $this->middleware->process($request, $handler);
    }

    public function testProcessThrowsExceptionForPostRequestWithInvalidToken(): void
    {
        $this->manager->generate($this->session);

        $request = $this->factory->createServerRequest('POST', '/test')
            ->withParsedBody(['_token' => 'invalid-token'])
            ->withAttribute('session', $this->session);

        $handler = $this->createMockHandler(new Response());

        $this->expectException(CsrfTokenMismatchException::class);

        $this->middleware->process($request, $handler);
    }

    public function testProcessThrowsExceptionForPutRequestWithoutToken(): void
    {
        $request = $this->factory->createServerRequest('PUT', '/test')
            ->withAttribute('session', $this->session);

        $handler = $this->createMockHandler(new Response());

        $this->expectException(CsrfTokenMismatchException::class);

        $this->middleware->process($request, $handler);
    }

    public function testProcessThrowsExceptionForDeleteRequestWithoutToken(): void
    {
        $request = $this->factory->createServerRequest('DELETE', '/test')
            ->withAttribute('session', $this->session);

        $handler = $this->createMockHandler(new Response());

        $this->expectException(CsrfTokenMismatchException::class);

        $this->middleware->process($request, $handler);
    }

    public function testProcessThrowsExceptionForPatchRequestWithoutToken(): void
    {
        $request = $this->factory->createServerRequest('PATCH', '/test')
            ->withAttribute('session', $this->session);

        $handler = $this->createMockHandler(new Response());

        $this->expectException(CsrfTokenMismatchException::class);

        $this->middleware->process($request, $handler);
    }

    public function testProcessPrefersHeaderOverBody(): void
    {
        $token = $this->manager->generate($this->session);

        $request = $this->factory->createServerRequest('POST', '/test')
            ->withHeader('X-CSRF-TOKEN', $token)
            ->withParsedBody(['_token' => 'wrong-token'])
            ->withAttribute('session', $this->session);

        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessHandlesMissingSessionAttribute(): void
    {
        $request = $this->factory->createServerRequest('POST', '/test')
            ->withParsedBody(['_token' => 'some-token']);

        $handler = $this->createMockHandler(new Response());

        $this->expectException(CsrfTokenMismatchException::class);

        $this->middleware->process($request, $handler);
    }

    public function testProcessHandlesNonArraySessionAttribute(): void
    {
        $request = $this->factory->createServerRequest('POST', '/test')
            ->withParsedBody(['_token' => 'some-token'])
            ->withAttribute('session', 'not-an-array');

        $handler = $this->createMockHandler(new Response());

        $this->expectException(CsrfTokenMismatchException::class);

        $this->middleware->process($request, $handler);
    }

    private function createMockHandler(Response $response): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        return $handler;
    }
}

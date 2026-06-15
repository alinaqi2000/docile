<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Auth;

use Docile\Security\Auth\AuthMiddleware;
use Docile\Security\Auth\SessionGuard;
use Docile\Security\Password\SodiumHasher;
use Docile\Security\Tests\Fixtures\TestUser;
use Docile\Security\Tests\Fixtures\TestUserProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(AuthMiddleware::class)]
final class AuthMiddlewareTest extends TestCase
{
    private AuthMiddleware $middleware;
    private TestUserProvider $provider;
    private SodiumHasher $hasher;
    private Psr17Factory $factory;
    private ClockInterface $clock;
    /** @var array<string, mixed> */
    private array $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new TestUserProvider();
        $this->hasher = new SodiumHasher();
        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('@' . time()));
        $this->middleware = new AuthMiddleware($this->provider, $this->hasher, 'secret', $this->clock);
        $this->factory = new Psr17Factory();
        $this->session = [];

        $user = new TestUser(1, 'user@example.com', $this->hasher->hash('password'), 'api-key-123');
        $this->provider->addUser($user);
    }

    public function testProcessAddsUserViaSession(): void
    {
        $guard = new SessionGuard($this->provider, $this->hasher);
        $guard->attempt(['email' => 'user@example.com', 'password' => 'password'], $this->session);

        $request = $this->factory->createServerRequest('GET', '/test')
            ->withAttribute('session', $this->session);

        $handler = $this->createHandlerThatChecksUser(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessAddsUserViaBearerToken(): void
    {
        $payload = json_encode(['sub' => 1, 'exp' => time() + 3600]);
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payloadEncoded = $this->base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $header . '.' . $payloadEncoded, 'secret', true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        $token = $header . '.' . $payloadEncoded . '.' . $signatureEncoded;

        $request = $this->factory->createServerRequest('GET', '/test')
            ->withHeader('Authorization', 'Bearer ' . $token);

        $handler = $this->createHandlerThatChecksUser(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessAddsUserViaApiKey(): void
    {
        $request = $this->factory->createServerRequest('GET', '/test')
            ->withHeader('X-API-Key', 'api-key-123');

        $handler = $this->createHandlerThatChecksUser(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessDoesNotAddUserWhenNoAuth(): void
    {
        $request = $this->factory->createServerRequest('GET', '/test');

        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($request->getAttribute('user'));
    }

    public function testProcessPrefersSessionOverOtherMethods(): void
    {
        $guard = new SessionGuard($this->provider, $this->hasher);
        $guard->attempt(['email' => 'user@example.com', 'password' => 'password'], $this->session);

        $request = $this->factory->createServerRequest('GET', '/test')
            ->withAttribute('session', $this->session)
            ->withHeader('Authorization', 'Bearer invalid-token')
            ->withHeader('X-API-Key', 'invalid-key');

        $handler = $this->createHandlerThatChecksUser(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessPrefersBearerTokenOverApiKey(): void
    {
        $payload = json_encode(['sub' => 1, 'exp' => time() + 3600]);
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payloadEncoded = $this->base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $header . '.' . $payloadEncoded, 'secret', true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        $token = $header . '.' . $payloadEncoded . '.' . $signatureEncoded;

        $request = $this->factory->createServerRequest('GET', '/test')
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-API-Key', 'invalid-key');

        $handler = $this->createHandlerThatChecksUser(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessHandlesInvalidBearerToken(): void
    {
        $request = $this->factory->createServerRequest('GET', '/test')
            ->withHeader('Authorization', 'Bearer invalid-token');

        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($request->getAttribute('user'));
    }

    public function testProcessHandlesInvalidApiKey(): void
    {
        $request = $this->factory->createServerRequest('GET', '/test')
            ->withHeader('X-API-Key', 'invalid-key');

        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($request->getAttribute('user'));
    }

    public function testProcessHandlesMissingBearerPrefix(): void
    {
        $request = $this->factory->createServerRequest('GET', '/test')
            ->withHeader('Authorization', 'invalid-token');

        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($request->getAttribute('user'));
    }

    public function testProcessHandlesExpiredToken(): void
    {
        $payload = json_encode(['sub' => 1, 'exp' => time() - 3600]);
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payloadEncoded = $this->base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $header . '.' . $payloadEncoded, 'secret', true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        $token = $header . '.' . $payloadEncoded . '.' . $signatureEncoded;

        $request = $this->factory->createServerRequest('GET', '/test')
            ->withHeader('Authorization', 'Bearer ' . $token);

        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($request->getAttribute('user'));
    }

    public function testProcessHandlesNonArraySession(): void
    {
        $request = $this->factory->createServerRequest('GET', '/test')
            ->withAttribute('session', 'not-an-array');

        $handler = $this->createMockHandler(new Response());

        $response = $this->middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($request->getAttribute('user'));
    }

    private function createMockHandler(Response $response): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        return $handler;
    }

    private function createHandlerThatChecksUser(Response $response): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($request) {
                return $request->getAttribute('user') !== null;
            }))
            ->willReturn($response);

        return $handler;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

<?php

declare(strict_types=1);

namespace Docile\Foundation\Tests;

use Docile\Container\Container;
use Docile\Foundation\Application;
use Docile\Foundation\BootstrapperInterface;
use Docile\Foundation\ExceptionHandler;
use Docile\Foundation\ServiceProviderInterface;
use Docile\Foundation\Tests\Fixtures\TestBootstrapper;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

#[CoversClass(Application::class)]
final class ApplicationTest extends TestCase
{
    private Container $container;
    private Application $app;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->app = new Application($this->container);
    }

    // -------------------------------------------------------------------------
    // bootstrap()
    // -------------------------------------------------------------------------

    public function testBootstrapCallsBootstrapper(): void
    {
        $bootstrapper = new TestBootstrapper();
        self::assertFalse($bootstrapper->wasCalled);

        $this->app->bootstrap($bootstrapper);

        self::assertTrue($bootstrapper->wasCalled);
    }

    public function testBootstrapPassesApplicationToBootstrapper(): void
    {
        $bootstrapper = new class implements BootstrapperInterface {
            public ?Application $captured = null;

            public function bootstrap(Application $app): void
            {
                $this->captured = $app;
            }
        };

        $this->app->bootstrap($bootstrapper);

        self::assertSame($this->app, $bootstrapper->captured);
    }

    // -------------------------------------------------------------------------
    // container()
    // -------------------------------------------------------------------------

    public function testContainerReturnsTheContainer(): void
    {
        self::assertSame($this->container, $this->app->container());
    }

    // -------------------------------------------------------------------------
    // register()
    // -------------------------------------------------------------------------

    public function testRegisterCallsProviderRegisterAndBoot(): void
    {
        $provider = new class implements \Docile\Foundation\ServiceProviderInterface {
            public bool $registerCalled = false;
            public bool $bootCalled = false;

            public function register(\Docile\Container\ContainerInterface $container): void
            {
                $this->registerCalled = true;
            }

            public function boot(\Docile\Container\ContainerInterface $container): void
            {
                $this->bootCalled = true;
            }
        };

        $this->container->instance($provider::class, $provider);

        $this->app->register($provider::class);

        self::assertTrue($provider->registerCalled);
        self::assertTrue($provider->bootCalled);
    }

    public function testRegisterThrowsWhenProviderDoesNotImplementInterface(): void
    {
        $this->container->instance('BadProvider', new \stdClass());

        $this->expectException(\Docile\Foundation\Exception\FoundationException::class);
        $this->expectExceptionMessage('does not implement ServiceProviderInterface');

        $this->app->register('BadProvider');
    }

    // -------------------------------------------------------------------------
    // handleHttp()
    // -------------------------------------------------------------------------

    public function testHandleHttpResolvesHandlerAndReturnsResponse(): void
    {
        $expectedResponse = $this->createMock(ResponseInterface::class);
        $request = new ServerRequest('GET', '/');

        $handler = new class ($expectedResponse) implements RequestHandlerInterface {
            public function __construct(private readonly ResponseInterface $response) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };

        $this->container->instance($handler::class, $handler);

        $response = $this->app->handleHttp($request, $handler::class);

        self::assertSame($expectedResponse, $response);
    }

    public function testHandleHttpReturnsErrorResponseWhenHandlerNotInContainer(): void
    {
        $request = new ServerRequest('GET', '/');

        $response = $this->app->handleHttp($request, 'NonExistentHandlerClass');

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('application/problem+json', $response->getHeaderLine('Content-Type'));
    }

    public function testHandleHttpReturnsErrorResponseWhenHandlerIsNotRequestHandler(): void
    {
        $request = new ServerRequest('GET', '/');

        // Bind something that is NOT a RequestHandlerInterface
        $this->container->instance('BadHandler', new \stdClass());

        $response = $this->app->handleHttp($request, 'BadHandler');

        self::assertSame(500, $response->getStatusCode());
    }

    public function testHandleHttpReturnsErrorResponseWhenHandlerThrows(): void
    {
        $request = new ServerRequest('GET', '/');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Something went wrong', 503);
            }
        };

        $this->container->instance($handler::class, $handler);

        $response = $this->app->handleHttp($request, $handler::class);

        self::assertSame(503, $response->getStatusCode());
    }

    public function testHandleHttpUsesCustomExceptionHandler(): void
    {
        $request = new ServerRequest('GET', '/');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('debug error');
            }
        };

        $this->container->instance($handler::class, $handler);

        // Use debug mode — trace included
        $app = new Application($this->container, new ExceptionHandler(debug: true));
        $response = $app->handleHttp($request, $handler::class);

        $body = (string) $response->getBody();
        self::assertStringContainsString('trace', $body);
    }
}

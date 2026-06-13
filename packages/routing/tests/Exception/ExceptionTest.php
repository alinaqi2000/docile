<?php

declare(strict_types=1);

namespace Docile\Routing\Tests\Exception;

use Docile\Routing\Exception\InvalidRouteException;
use Docile\Routing\Exception\MethodNotAllowedRoutingException;
use Docile\Routing\Exception\NotFoundRoutingException;
use Docile\Routing\Exception\RouteNotFoundException;
use Docile\Routing\Exception\RoutingException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

#[CoversClass(RoutingException::class)]
#[CoversClass(NotFoundRoutingException::class)]
#[CoversClass(MethodNotAllowedRoutingException::class)]
#[CoversClass(RouteNotFoundException::class)]
#[CoversClass(InvalidRouteException::class)]
final class ExceptionTest extends TestCase
{
    public function testRoutingExceptionExtendsRuntimeException(): void
    {
        $e = new RoutingException('base error');

        self::assertSame('base error', $e->getMessage());

        $parent = (new ReflectionClass($e))->getParentClass();
        self::assertNotFalse($parent);
        self::assertSame(RuntimeException::class, $parent->getName());
    }

    public function testNotFoundRoutingExceptionExtendsRoutingException(): void
    {
        $e = new NotFoundRoutingException('not found');

        self::assertSame('not found', $e->getMessage());

        $parent = (new ReflectionClass($e))->getParentClass();
        self::assertNotFalse($parent);
        self::assertSame(RoutingException::class, $parent->getName());
    }

    public function testRouteNotFoundExceptionExtendsRoutingException(): void
    {
        $e = new RouteNotFoundException('no named route');

        self::assertSame('no named route', $e->getMessage());

        $parent = (new ReflectionClass($e))->getParentClass();
        self::assertNotFalse($parent);
        self::assertSame(RoutingException::class, $parent->getName());
    }

    public function testInvalidRouteExceptionExtendsRoutingException(): void
    {
        $e = new InvalidRouteException('bad route');

        self::assertSame('bad route', $e->getMessage());

        $parent = (new ReflectionClass($e))->getParentClass();
        self::assertNotFalse($parent);
        self::assertSame(RoutingException::class, $parent->getName());
    }

    public function testMethodNotAllowedExceptionExtendsRoutingException(): void
    {
        $e = new MethodNotAllowedRoutingException(['GET']);

        $parent = (new ReflectionClass($e))->getParentClass();
        self::assertNotFalse($parent);
        self::assertSame(RoutingException::class, $parent->getName());
    }

    public function testMethodNotAllowedExceptionCarriesAllowedMethods(): void
    {
        $allowed = ['GET', 'POST'];
        $e = new MethodNotAllowedRoutingException($allowed);

        self::assertSame($allowed, $e->getAllowedMethods());
        self::assertStringContainsString('GET', $e->getMessage());
        self::assertStringContainsString('POST', $e->getMessage());
    }

    public function testMethodNotAllowedExceptionAcceptsCustomMessage(): void
    {
        $e = new MethodNotAllowedRoutingException(['DELETE'], 'custom message');

        self::assertSame('custom message', $e->getMessage());
        self::assertSame(['DELETE'], $e->getAllowedMethods());
    }

    public function testMethodNotAllowedExceptionWithPrevious(): void
    {
        $previous = new RuntimeException('previous');
        $e = new MethodNotAllowedRoutingException(['PUT'], '', 0, $previous);

        self::assertSame($previous, $e->getPrevious());
    }

    public function testRoutingExceptionDefaultCode(): void
    {
        $e = new RoutingException('error');

        self::assertSame(0, $e->getCode());
    }

    public function testRoutingExceptionCustomCode(): void
    {
        $e = new RoutingException('error', 404);

        self::assertSame(404, $e->getCode());
    }
}

<?php

declare(strict_types=1);

namespace Docile\Bus\Tests\Middleware;

use Docile\Bus\Exception\ReentrantDispatchException;
use Docile\Bus\Middleware\LockingMiddleware;
use Docile\Bus\Tests\Fixtures\CreateUserCommand;
use Docile\Bus\Tests\Fixtures\GetUserQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LockingMiddleware::class)]
#[CoversClass(ReentrantDispatchException::class)]
final class LockingMiddlewareTest extends TestCase
{
    public function testAllowsNormalDispatch(): void
    {
        $middleware = new LockingMiddleware();
        $command = new CreateUserCommand('Alice', 'alice@test.com');

        $result = $middleware->handle($command, static fn(object $msg): string => 'ok');

        self::assertSame('ok', $result);
    }

    public function testThrowsOnReentrantDispatchOfSameClass(): void
    {
        $middleware = new LockingMiddleware();
        $command = new CreateUserCommand('Bob', 'bob@test.com');

        $this->expectException(ReentrantDispatchException::class);
        $this->expectExceptionMessage('already being handled');

        $middleware->handle($command, static function (object $msg) use ($middleware): mixed {
            return $middleware->handle(
                new CreateUserCommand('Re-entrant', 're@test.com'),
                static fn(object $msg): string => 'should not reach',
            );
        });
    }

    public function testAllowsDifferentMessageClassesConcurrently(): void
    {
        $middleware = new LockingMiddleware();
        $command = new CreateUserCommand('Alice', 'alice@test.com');

        $result = $middleware->handle($command, static function (object $msg) use ($middleware): string {
            $innerResult = $middleware->handle(
                new GetUserQuery(1),
                static fn(object $msg): string => 'query-ok',
            );

            /** @var string $innerResult */

            return 'cmd-ok:' . $innerResult;
        });

        self::assertSame('cmd-ok:query-ok', $result);
    }

    public function testLockIsReleasedAfterHandling(): void
    {
        $middleware = new LockingMiddleware();
        $command = new CreateUserCommand('Alice', 'alice@test.com');

        $middleware->handle($command, static fn(object $msg): string => 'first');
        $result = $middleware->handle($command, static fn(object $msg): string => 'second');

        self::assertSame('second', $result);
    }

    public function testLockIsReleasedOnException(): void
    {
        $middleware = new LockingMiddleware();
        $command = new CreateUserCommand('Alice', 'alice@test.com');

        try {
            $middleware->handle($command, static function (object $msg): never {
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
            // expected
        }

        // Lock should be released — dispatch same class again should succeed
        $result = $middleware->handle($command, static fn(object $msg): string => 'recovered');

        self::assertSame('recovered', $result);
    }
}

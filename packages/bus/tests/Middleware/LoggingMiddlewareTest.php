<?php

declare(strict_types=1);

namespace Docile\Bus\Tests\Middleware;

use Docile\Bus\Middleware\LoggingMiddleware;
use Docile\Bus\Tests\Fixtures\CreateUserCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoggingMiddleware::class)]
final class LoggingMiddlewareTest extends TestCase
{
    public function testLogsBeforeAndAfterHandling(): void
    {
        /** @var list<string> $logs */
        $logs = [];

        $middleware = new LoggingMiddleware(static function (string $message) use (&$logs): void {
            $logs[] = $message;
        });

        $command = new CreateUserCommand('Alice', 'alice@test.com');
        $result = $middleware->handle($command, static fn(object $msg): string => 'ok');

        self::assertSame('ok', $result);
        self::assertCount(2, $logs);
        self::assertSame('Dispatching: ' . CreateUserCommand::class, $logs[0]);
        self::assertSame('Handled: ' . CreateUserCommand::class, $logs[1]);
    }

    public function testPassesThroughReturnValue(): void
    {
        $middleware = new LoggingMiddleware(static function (string $message): void {});

        $result = $middleware->handle(
            new CreateUserCommand('Bob', 'bob@test.com'),
            static fn(object $msg): int => 42,
        );

        self::assertSame(42, $result);
    }

    public function testDelegatesMessageToNext(): void
    {
        $middleware = new LoggingMiddleware(static function (string $message): void {});

        $received = null;
        $command = new CreateUserCommand('Charlie', 'charlie@test.com');
        $middleware->handle($command, static function (object $msg) use (&$received): string {
            $received = $msg;

            return 'handled';
        });

        self::assertSame($command, $received);
    }
}

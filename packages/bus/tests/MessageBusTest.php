<?php

declare(strict_types=1);

namespace Docile\Bus\Tests;

use Docile\Bus\Exception\HandlerNotFoundException;
use Docile\Bus\MapLocator;
use Docile\Bus\MessageBus;
use Docile\Bus\Tests\Fixtures\CreateUserCommand;
use Docile\Bus\Tests\Fixtures\CreateUserHandler;
use Docile\Bus\Tests\Fixtures\TracingMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(MessageBus::class)]
#[CoversClass(MapLocator::class)]
#[CoversClass(HandlerNotFoundException::class)]
final class MessageBusTest extends TestCase
{
    public function testDispatchRoutesToCorrectHandler(): void
    {
        $handler = new CreateUserHandler();
        $locator = new MapLocator([
            CreateUserCommand::class => static function (object $msg) use ($handler): mixed {
                \assert($msg instanceof CreateUserCommand);

                return $handler($msg);
            },
        ]);

        $bus = new MessageBus($locator);
        $result = $bus->dispatch(new CreateUserCommand('Alice', 'alice@test.com'));

        self::assertSame('created:Alice', $result);
    }

    public function testDispatchThrowsWhenNoHandlerRegistered(): void
    {
        $locator = new MapLocator();
        $bus = new MessageBus($locator);

        $this->expectException(HandlerNotFoundException::class);
        $this->expectExceptionMessage('No handler registered for message');

        $bus->dispatch(new stdClass());
    }

    public function testMiddlewareExecutesInCorrectOrder(): void
    {
        /** @var list<string> $order */
        $order = [];

        $middleware1 = new TracingMiddleware($order, 'mw1');
        $middleware2 = new TracingMiddleware($order, 'mw2');

        $locator = new MapLocator([
            CreateUserCommand::class => static function (object $msg) use (&$order): string {
                \assert($msg instanceof CreateUserCommand);
                $order[] = 'handler';

                return 'created:' . $msg->name;
            },
        ]);

        $bus = new MessageBus($locator, [$middleware1, $middleware2]);
        $result = $bus->dispatch(new CreateUserCommand('Bob', 'bob@test.com'));

        self::assertSame('created:Bob', $result);
        self::assertSame(['mw1:before', 'mw2:before', 'handler', 'mw2:after', 'mw1:after'], $order);
    }

    public function testDispatchWithNoMiddlewareCallsHandlerDirectly(): void
    {
        $called = false;
        $locator = new MapLocator([
            stdClass::class => static function (object $msg) use (&$called): string {
                $called = true;

                return 'done';
            },
        ]);

        $bus = new MessageBus($locator);
        $result = $bus->dispatch(new stdClass());

        self::assertTrue($called);
        self::assertSame('done', $result);
    }

    public function testDispatchReturnsNullFromVoidHandler(): void
    {
        $locator = new MapLocator([
            stdClass::class => static function (object $msg): void {},
        ]);

        $bus = new MessageBus($locator);
        $result = $bus->dispatch(new stdClass());

        self::assertNull($result);
    }
}

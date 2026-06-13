<?php

declare(strict_types=1);

namespace Docile\Bus\Tests;

use Docile\Bus\CommandBus;
use Docile\Bus\MapLocator;
use Docile\Bus\MessageBus;
use Docile\Bus\Tests\Fixtures\CreateUserCommand;
use Docile\Bus\Tests\Fixtures\CreateUserHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommandBus::class)]
final class CommandBusTest extends TestCase
{
    public function testDispatchDelegatesToUnderlyingBus(): void
    {
        $handler = new CreateUserHandler();
        $locator = new MapLocator([
            CreateUserCommand::class => static function (object $msg) use ($handler): mixed {
                \assert($msg instanceof CreateUserCommand);

                return $handler($msg);
            },
        ]);

        $commandBus = new CommandBus(new MessageBus($locator));
        $result = $commandBus->dispatch(new CreateUserCommand('Charlie', 'charlie@test.com'));

        self::assertSame('created:Charlie', $result);
    }
}

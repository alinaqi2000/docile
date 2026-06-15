<?php

declare(strict_types=1);

namespace Docile\Bus\Tests;

use Docile\Bus\Exception\HandlerNotFoundException;
use Docile\Bus\MapLocator;
use Docile\Bus\Tests\Fixtures\CreateUserCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(MapLocator::class)]
#[CoversClass(HandlerNotFoundException::class)]
final class MapLocatorTest extends TestCase
{
    public function testGetHandlerReturnsRegisteredHandler(): void
    {
        $handler = static fn(object $msg): string => 'handled';
        $locator = new MapLocator([
            CreateUserCommand::class => $handler,
        ]);

        $result = $locator->getHandler(new CreateUserCommand('Test', 'test@test.com'));

        self::assertSame($handler, $result);
    }

    public function testRegisterAddsHandler(): void
    {
        $locator = new MapLocator();
        $handler = static fn(object $msg): string => 'handled';

        $locator->register(CreateUserCommand::class, $handler);

        self::assertSame($handler, $locator->getHandler(new CreateUserCommand('Test', 'test@test.com')));
    }

    public function testGetHandlerThrowsForUnknownMessage(): void
    {
        $locator = new MapLocator();

        $this->expectException(HandlerNotFoundException::class);
        $this->expectExceptionMessage('No handler registered for message "stdClass"');

        $locator->getHandler(new stdClass());
    }

    public function testRegisterOverwritesPreviousHandler(): void
    {
        $handler1 = static fn(object $msg): string => 'first';
        $handler2 = static fn(object $msg): string => 'second';

        $locator = new MapLocator();
        $locator->register(CreateUserCommand::class, $handler1);
        $locator->register(CreateUserCommand::class, $handler2);

        self::assertSame($handler2, $locator->getHandler(new CreateUserCommand('Test', 'test@test.com')));
    }
}

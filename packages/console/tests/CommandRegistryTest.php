<?php

declare(strict_types=1);

namespace Docile\Console\Tests;

use Docile\Console\CommandRegistry;
use Docile\Console\Exception\CommandNotFoundException;
use Docile\Console\Exception\InvalidCommandException;
use Docile\Console\Tests\Fixtures\GreetCommand;
use Docile\Console\Tests\Fixtures\ListAllCommand;
use PHPUnit\Framework\TestCase;

final class CommandRegistryTest extends TestCase
{
    private CommandRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new CommandRegistry();
    }

    public function testRegisterValidCommand(): void
    {
        $this->registry->register(GreetCommand::class);
        
        $all = $this->registry->all();
        $this->assertArrayHasKey('greet', $all);
        $this->assertSame(GreetCommand::class, $all['greet']);
    }

    public function testFindRegisteredCommand(): void
    {
        $this->registry->register(GreetCommand::class);
        
        $commandClass = $this->registry->find('greet');
        $this->assertSame(GreetCommand::class, $commandClass);
    }

    public function testFindUnregisteredCommandThrowsException(): void
    {
        $this->expectException(CommandNotFoundException::class);
        $this->expectExceptionMessage("Command 'nonexistent' not found.");
        
        $this->registry->find('nonexistent');
    }

    public function testRegisterClassWithoutAsCommandAttributeThrowsException(): void
    {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage("Command Docile\\Console\\Tests\\Fixtures\\InvalidCommand must have #[AsCommand] attribute.");
        
        $this->registry->register(\Docile\Console\Tests\Fixtures\InvalidCommand::class);
    }

    public function testRegisterClassNotExtendingCommandThrowsException(): void
    {
        $this->expectException(InvalidCommandException::class);
        $this->expectExceptionMessage("Class stdClass must extend Command.");
        
        /** @phpstan-ignore-next-line */
        $this->registry->register(\stdClass::class);
    }

    public function testAllReturnsEmptyArrayWhenNoCommandsRegistered(): void
    {
        $all = $this->registry->all();
        $this->assertEmpty($all);
    }

    public function testRegisterMultipleCommands(): void
    {
        $this->registry->register(GreetCommand::class);
        $this->registry->register(ListAllCommand::class);
        
        $all = $this->registry->all();
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('greet', $all);
        $this->assertArrayHasKey('list:all', $all);
        $this->assertSame(GreetCommand::class, $all['greet']);
        $this->assertSame(ListAllCommand::class, $all['list:all']);
    }
}
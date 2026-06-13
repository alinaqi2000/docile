<?php

declare(strict_types=1);

namespace Docile\Console\Tests;

use Docile\Console\CommandRegistry;
use Docile\Console\Kernel;
use Docile\Console\Output;
use Docile\Console\Tests\Fixtures\GreetCommand;
use Docile\Console\Tests\Fixtures\ListAllCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class KernelTest extends TestCase
{
    private ContainerInterface $container;
    private CommandRegistry $registry;
    private Kernel $kernel;
    private mixed $outputStream;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->registry = new CommandRegistry();
        $this->kernel = new Kernel($this->registry, $this->container);
        $this->outputStream = fopen('php://memory', 'rw');
    }

    protected function tearDown(): void
    {
        fclose($this->outputStream);
    }

    public function testHandleWithNoArgumentsListsCommands(): void
    {
        $this->registry->register(GreetCommand::class);
        $output = new Output($this->outputStream);
        
        $exitCode = $this->kernel->handle(['script.php'], $output);
        
        $this->assertSame(0, $exitCode);
        
        rewind($this->outputStream);
        $content = stream_get_contents($this->outputStream);
        
        $this->assertStringContainsString('Available commands:', $content);
        $this->assertStringContainsString('greet', $content);
        $this->assertStringContainsString('Greet someone with a message', $content);
    }

    public function testHandleWithListCommandListsCommands(): void
    {
        $this->registry->register(GreetCommand::class);
        $output = new Output($this->outputStream);
        
        $exitCode = $this->kernel->handle(['script.php', 'list'], $output);
        
        $this->assertSame(0, $exitCode);
        
        rewind($this->outputStream);
        $content = stream_get_contents($this->outputStream);
        
        $this->assertStringContainsString('Available commands:', $content);
        $this->assertStringContainsString('greet', $content);
    }

    public function testHandleWithValidCommand(): void
    {
        $this->registry->register(GreetCommand::class);
        
        $command = new GreetCommand();
        $this->container->method('has')->with(GreetCommand::class)->willReturn(true);
        $this->container->method('get')->with(GreetCommand::class)->willReturn($command);
        
        $output = new Output($this->outputStream);
        
        $exitCode = $this->kernel->handle(['script.php', 'greet', 'John'], $output);
        
        $this->assertSame(0, $exitCode);
        
        rewind($this->outputStream);
        $content = stream_get_contents($this->outputStream);
        
        $this->assertStringContainsString('Hello, John!', $content);
    }

    public function testHandleWithNonExistentCommandReturnsError(): void
    {
        $output = new Output($this->outputStream);
        
        $exitCode = $this->kernel->handle(['script.php', 'nonexistent'], $output);
        
        $this->assertSame(1, $exitCode);
        
        rewind($this->outputStream);
        $content = stream_get_contents($this->outputStream);
        
        $this->assertStringContainsString("[ERROR] Command 'nonexistent' not found.", $content);
    }

    public function testHandleWithCommandNotInContainerReturnsError(): void
    {
        $this->registry->register(GreetCommand::class);
        $this->container->method('has')->with(GreetCommand::class)->willReturn(false);
        
        $output = new Output($this->outputStream);
        
        $exitCode = $this->kernel->handle(['script.php', 'greet'], $output);
        
        $this->assertSame(1, $exitCode);
        
        rewind($this->outputStream);
        $content = stream_get_contents($this->outputStream);
        
        $this->assertStringContainsString("[ERROR] Command 'Docile\\Console\\Tests\\Fixtures\\GreetCommand' is not registered in the container.", $content);
    }

    public function testHandleWithContainerReturningNonCommandReturnsError(): void
    {
        $this->registry->register(GreetCommand::class);
        $this->container->method('has')->with(GreetCommand::class)->willReturn(true);
        $this->container->method('get')->with(GreetCommand::class)->willReturn(new \stdClass());
        
        $output = new Output($this->outputStream);
        
        $exitCode = $this->kernel->handle(['script.php', 'greet'], $output);
        
        $this->assertSame(1, $exitCode);
        
        rewind($this->outputStream);
        $content = stream_get_contents($this->outputStream);
        
        $this->assertStringContainsString("[ERROR] Resolved command 'Docile\\Console\\Tests\\Fixtures\\GreetCommand' is not a Command instance.", $content);
    }

    public function testHandleWithEmptyRegistryShowsNoCommandsMessage(): void
    {
        $output = new Output($this->outputStream);
        
        $exitCode = $this->kernel->handle(['script.php'], $output);
        
        $this->assertSame(0, $exitCode);
        
        rewind($this->outputStream);
        $content = stream_get_contents($this->outputStream);
        
        $this->assertStringContainsString('No commands registered.', $content);
    }
}
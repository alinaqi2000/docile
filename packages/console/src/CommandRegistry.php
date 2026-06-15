<?php

declare(strict_types=1);

namespace Docile\Console;

use Docile\Console\Attribute\AsCommand;
use Docile\Console\Exception\CommandNotFoundException;
use Docile\Console\Exception\InvalidCommandException;
use ReflectionClass;

final class CommandRegistry
{
    /** @var array<string, class-string<Command>> */
    private array $commands = [];

    /**
     * @param class-string<Command> $commandClass
     */
    public function register(string $commandClass): void
    {
        $reflection = new ReflectionClass($commandClass);
        
        if (!$reflection->isSubclassOf(Command::class)) {
            throw new InvalidCommandException("Class {$commandClass} must extend Command.");
        }

        $attributes = $reflection->getAttributes(AsCommand::class);
        
        if ($attributes === []) {
            throw new InvalidCommandException("Command {$commandClass} must have #[AsCommand] attribute.");
        }

        $asCommand = $attributes[0]->newInstance();
        $this->commands[$asCommand->name] = $commandClass;
    }

    /**
     * @return class-string<Command>
     */
    public function find(string $name): string
    {
        if (!isset($this->commands[$name])) {
            throw new CommandNotFoundException("Command '{$name}' not found.");
        }

        return $this->commands[$name];
    }

    /**
     * @return array<string, class-string<Command>>
     */
    public function all(): array
    {
        return $this->commands;
    }
}
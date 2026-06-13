<?php

declare(strict_types=1);

namespace Docile\Console;

use Docile\Console\Exception\CommandNotFoundException;
use Psr\Container\ContainerInterface;

final class Kernel
{
    public function __construct(
        private readonly CommandRegistry $registry,
        private readonly ContainerInterface $container,
    ) {}

    /**
     * @param array<int, string> $argv
     */
    public function handle(array $argv, Output $output): int
    {
        if (count($argv) < 2) {
            $this->listCommands($output);
            return 0;
        }

        $commandName = $argv[1];

        if ($commandName === 'list') {
            $this->listCommands($output);
            return 0;
        }

        try {
            $commandClass = $this->registry->find($commandName);
        } catch (CommandNotFoundException $e) {
            $output->error($e->getMessage());
            return 1;
        }

        if (!$this->container->has($commandClass)) {
            $output->error("Command '{$commandClass}' is not registered in the container.");
            return 1;
        }

        $command = $this->container->get($commandClass);
        
        if (!$command instanceof Command) {
            $output->error("Resolved command '{$commandClass}' is not a Command instance.");
            return 1;
        }

        $definition = new InputDefinition();
        $command->configure($definition);

        $commandArgv = array_slice($argv, 2);
        $input = new Input($commandArgv, $definition);

        return $command->run($input, $output);
    }

    private function listCommands(Output $output): void
    {
        $commands = $this->registry->all();
        
        if ($commands === []) {
            $output->writeln('No commands registered.');
            return;
        }

        $output->writeln('Available commands:');
        $output->writeln('');

        $headers = ['Command', 'Description'];
        $rows = [];

        foreach ($commands as $name => $class) {
            $description = $class::getDescription();
            $rows[] = [$name, $description];
        }

        $output->table($headers, $rows);
    }
}
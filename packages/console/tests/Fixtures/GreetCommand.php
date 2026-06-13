<?php

declare(strict_types=1);

namespace Docile\Console\Tests\Fixtures;

use Docile\Console\Attribute\AsCommand;
use Docile\Console\Command;
use Docile\Console\InputArgument;
use Docile\Console\InputDefinition;
use Docile\Console\InputOption;
use Docile\Console\Input;
use Docile\Console\Output;

#[AsCommand('greet', 'Greet someone with a message')]
final class GreetCommand extends Command
{
    public function configure(InputDefinition $definition): void
    {
        $definition->addArgument(new InputArgument('name', InputArgument::OPTIONAL, 'The name to greet', 'World'));
        $definition->addOption(new InputOption('shout', 's', InputOption::VALUE_NONE, 'Shout the greeting'));
        $definition->addOption(new InputOption('times', 't', InputOption::VALUE_OPTIONAL, 'Number of times to greet', 1));
    }

    protected function handle(Input $input, Output $output): int
    {
        $name = $input->argument('name');
        $times = (int) $input->option('times');
        $shout = $input->hasOption('shout');

        for ($i = 0; $i < $times; $i++) {
            $message = "Hello, {$name}!";
            if ($shout) {
                $message = strtoupper($message);
            }
            $output->writeln($message);
        }

        return 0;
    }
}
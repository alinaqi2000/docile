<?php

declare(strict_types=1);

namespace Docile\Console\Tests\Fixtures;

use Docile\Console\Attribute\AsCommand;
use Docile\Console\Command;
use Docile\Console\Input;
use Docile\Console\Output;

#[AsCommand('list:all', 'List all available commands')]
final class ListAllCommand extends Command
{
    protected function handle(Input $input, Output $output): int
    {
        $output->writeln('This is the list:all command');
        $output->writeln('It would list all commands if implemented');
        return 0;
    }
}
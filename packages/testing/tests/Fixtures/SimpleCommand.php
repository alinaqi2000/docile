<?php

declare(strict_types=1);

namespace Docile\Testing\Tests\Fixtures;

use Docile\Console\Command;
use Docile\Console\Input;
use Docile\Console\InputDefinition;
use Docile\Console\Output;

final class SimpleCommand extends Command
{
    public static function getName(): string
    {
        return 'simple';
    }

    public static function getDescription(): string
    {
        return 'A simple test command';
    }

    public function configure(InputDefinition $definition): void
    {
    }

    public function run(Input $input, Output $output): int
    {
        $output->writeln('Simple command executed');
        return 0;
    }
}

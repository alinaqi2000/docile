<?php

declare(strict_types=1);

namespace Docile\Console\Tests\Fixtures;

use Docile\Console\Command;

final class InvalidCommand extends Command
{
    protected function handle(\Docile\Console\Input $input, \Docile\Console\Output $output): int
    {
        return 0;
    }
}
<?php

declare(strict_types=1);

namespace Docile\Testing\Console;

use Docile\Console\Kernel as ConsoleKernel;
use Docile\Testing\ApplicationTestCase;

abstract class ConsoleTestCase extends ApplicationTestCase
{
    private TestOutput $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new TestOutput();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function testOutput(): TestOutput
    {
        return $this->output;
    }

    /**
     * @param array<string, string> $args
     * @phpstan-ignore argument.type, argument.type, argument.type, argument.type
     */
    protected function runCommand(string $name, array $args = []): int
    {
        $argv = ['docile', $name, ...array_values($args)];

        $kernel = $this->container()->get(ConsoleKernel::class);

        if (!$kernel instanceof ConsoleKernel) {
            throw new \RuntimeException('Console kernel not found in container');
        }

        return $kernel->handle($argv, $this->output);
    }
}

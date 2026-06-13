<?php

declare(strict_types=1);

namespace Docile\Console\Tests;

use Docile\Console\Exception\ConsoleException;
use Docile\Console\Input;
use Docile\Console\InputArgument;
use Docile\Console\InputDefinition;
use Docile\Console\InputOption;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    public function testParseRequiredArguments(): void
    {
        $definition = new InputDefinition();
        $definition->addArgument(new InputArgument('name', InputArgument::REQUIRED));
        
        $input = new Input(['John'], $definition);
        
        $this->assertSame('John', $input->argument('name'));
    }

    public function testParseOptionalArgumentsWithDefault(): void
    {
        $definition = new InputDefinition();
        $definition->addArgument(new InputArgument('name', InputArgument::OPTIONAL, 'Name', 'World'));
        
        $input = new Input([], $definition);
        
        $this->assertSame('World', $input->argument('name'));
    }

    public function testParseOptionalArgumentsWithValue(): void
    {
        $definition = new InputDefinition();
        $definition->addArgument(new InputArgument('name', InputArgument::OPTIONAL, 'Name', 'World'));
        
        $input = new Input(['John'], $definition);
        
        $this->assertSame('John', $input->argument('name'));
    }

    public function testRequiredArgumentMissingThrowsException(): void
    {
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage("Argument 'name' is required.");
        
        $definition = new InputDefinition();
        $definition->addArgument(new InputArgument('name', InputArgument::REQUIRED));
        
        new Input([], $definition);
    }

    public function testParseValueNoneOption(): void
    {
        $definition = new InputDefinition();
        $definition->addOption(new InputOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose mode'));
        
        $input = new Input(['--verbose'], $definition);
        
        $this->assertTrue($input->option('verbose'));
        $this->assertTrue($input->hasOption('verbose'));
    }

    public function testParseValueNoneOptionWithShortcut(): void
    {
        $definition = new InputDefinition();
        $definition->addOption(new InputOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose mode'));
        
        $input = new Input(['-v'], $definition);
        
        $this->assertTrue($input->option('verbose'));
        $this->assertTrue($input->hasOption('verbose'));
    }

    public function testParseValueRequiredOption(): void
    {
        $definition = new InputDefinition();
        $definition->addOption(new InputOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file'));
        
        $input = new Input(['--output', 'file.txt'], $definition);
        
        $this->assertSame('file.txt', $input->option('output'));
        $this->assertTrue($input->hasOption('output'));
    }

    public function testParseValueRequiredOptionWithEquals(): void
    {
        $definition = new InputDefinition();
        $definition->addOption(new InputOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file'));
        
        $input = new Input(['--output=file.txt'], $definition);
        
        $this->assertSame('file.txt', $input->option('output'));
        $this->assertTrue($input->hasOption('output'));
    }

    public function testParseValueRequiredOptionWithShortcut(): void
    {
        $definition = new InputDefinition();
        $definition->addOption(new InputOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file'));
        
        $input = new Input(['-o', 'file.txt'], $definition);
        
        $this->assertSame('file.txt', $input->option('output'));
        $this->assertTrue($input->hasOption('output'));
    }

    public function testValueRequiredOptionMissingValueThrowsException(): void
    {
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage("Option '--output' requires a value.");
        
        $definition = new InputDefinition();
        $definition->addOption(new InputOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file'));
        
        new Input(['--output'], $definition);
    }

    public function testParseValueOptionalOptionWithValue(): void
    {
        $definition = new InputDefinition();
        $definition->addOption(new InputOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format', 'text'));
        
        $input = new Input(['--format', 'json'], $definition);
        
        $this->assertSame('json', $input->option('format'));
        $this->assertTrue($input->hasOption('format'));
    }

    public function testParseValueOptionalOptionWithoutValue(): void
    {
        $definition = new InputDefinition();
        $definition->addOption(new InputOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format', 'text'));
        
        $input = new Input(['--format'], $definition);
        
        $this->assertSame('text', $input->option('format'));
        $this->assertTrue($input->hasOption('format'));
    }

    public function testParseValueOptionalOptionNotProvided(): void
    {
        $definition = new InputDefinition();
        $definition->addOption(new InputOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format', 'text'));
        
        $input = new Input([], $definition);
        
        $this->assertSame('text', $input->option('format'));
        $this->assertFalse($input->hasOption('format'));
    }

    public function testUndefinedArgumentThrowsException(): void
    {
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage("Argument 'nonexistent' is not defined.");
        
        $definition = new InputDefinition();
        $input = new Input([], $definition);
        $input->argument('nonexistent');
    }

    public function testUndefinedOptionThrowsException(): void
    {
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage("Option 'nonexistent' is not defined.");
        
        $definition = new InputDefinition();
        $input = new Input([], $definition);
        $input->option('nonexistent');
    }

    public function testComplexParsing(): void
    {
        $definition = new InputDefinition();
        $definition->addArgument(new InputArgument('command', InputArgument::REQUIRED));
        $definition->addArgument(new InputArgument('target', InputArgument::OPTIONAL, 'Target', 'default'));
        $definition->addOption(new InputOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose'));
        $definition->addOption(new InputOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output'));
        
        $input = new Input(['deploy', 'production', '--verbose', '--output', 'log.txt'], $definition);
        
        $this->assertSame('deploy', $input->argument('command'));
        $this->assertSame('production', $input->argument('target'));
        $this->assertTrue($input->option('verbose'));
        $this->assertTrue($input->hasOption('verbose'));
        $this->assertSame('log.txt', $input->option('output'));
        $this->assertTrue($input->hasOption('output'));
    }
}
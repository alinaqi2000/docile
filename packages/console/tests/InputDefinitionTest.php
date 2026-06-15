<?php

declare(strict_types=1);

namespace Docile\Console\Tests;

use Docile\Console\InputArgument;
use Docile\Console\InputDefinition;
use Docile\Console\InputOption;
use PHPUnit\Framework\TestCase;

final class InputDefinitionTest extends TestCase
{
    public function testAddAndGetArguments(): void
    {
        $definition = new InputDefinition();
        $argument = new InputArgument('test', InputArgument::REQUIRED, 'Test argument');
        
        $definition->addArgument($argument);
        
        $this->assertTrue($definition->hasArgument('test'));
        $this->assertFalse($definition->hasArgument('nonexistent'));
        $this->assertSame($argument, $definition->getArguments()['test']);
    }

    public function testAddAndGetOptions(): void
    {
        $definition = new InputDefinition();
        $option = new InputOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose output');
        
        $definition->addOption($option);
        
        $this->assertTrue($definition->hasOption('verbose'));
        $this->assertTrue($definition->hasOption('v'));
        $this->assertFalse($definition->hasOption('nonexistent'));
        $this->assertSame($option, $definition->getOptions()['verbose']);
        $this->assertSame($option, $definition->getOptionByShortcut('v'));
    }

    public function testGetOptionByNameReturnsCorrectOption(): void
    {
        $definition = new InputDefinition();
        $option = new InputOption('test', 't', InputOption::VALUE_REQUIRED, 'Test option');
        
        $definition->addOption($option);
        
        $this->assertSame($option, $definition->getOptionByName('test'));
        $this->assertNull($definition->getOptionByName('nonexistent'));
    }

    public function testGetOptionByShortcutReturnsCorrectOption(): void
    {
        $definition = new InputDefinition();
        $option = new InputOption('test', 't', InputOption::VALUE_REQUIRED, 'Test option');
        
        $definition->addOption($option);
        
        $this->assertSame($option, $definition->getOptionByShortcut('t'));
        $this->assertNull($definition->getOptionByShortcut('x'));
    }

    public function testOptionWithoutShortcut(): void
    {
        $definition = new InputDefinition();
        $option = new InputOption('test', null, InputOption::VALUE_NONE, 'Test option');
        
        $definition->addOption($option);
        
        $this->assertTrue($definition->hasOption('test'));
        $this->assertFalse($definition->hasOption('t'));
        $this->assertNull($definition->getOptionByShortcut('t'));
    }
}
<?php

declare(strict_types=1);

namespace Docile\Console;

final class InputDefinition
{
    /** @var array<string, InputArgument> */
    private array $arguments = [];

    /** @var array<string, InputOption> */
    private array $options = [];

    /** @var array<string, string> */
    private array $shortcuts = [];

    public function addArgument(InputArgument $argument): void
    {
        $this->arguments[$argument->name] = $argument;
    }

    public function addOption(InputOption $option): void
    {
        $this->options[$option->name] = $option;
        
        if ($option->shortcut !== null) {
            $this->shortcuts[$option->shortcut] = $option->name;
        }
    }

    /** @return array<string, InputArgument> */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /** @return array<string, InputOption> */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]) || isset($this->shortcuts[$name]);
    }

    public function getOptionByName(string $name): ?InputOption
    {
        return $this->options[$name] ?? null;
    }

    public function getOptionByShortcut(string $shortcut): ?InputOption
    {
        $optionName = $this->shortcuts[$shortcut] ?? null;
        
        return $optionName !== null ? $this->options[$optionName] : null;
    }
}
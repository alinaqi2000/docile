<?php

declare(strict_types=1);

namespace Docile\Console;

use Docile\Console\Exception\ConsoleException;

final class Input
{
    /** @var array<string, mixed> */
    private array $parsedArguments = [];

    /** @var array<string, mixed> */
    private array $parsedOptions = [];

    /** @var array<string, bool> */
    private array $explicitOptions = [];

    /**
     * @param array<int, string> $argv
     */
    public function __construct(array $argv, private readonly InputDefinition $definition)
    {
        $this->parse($argv);
    }

    public function argument(string $name): mixed
    {
        if (!$this->definition->hasArgument($name)) {
            throw new ConsoleException("Argument '{$name}' is not defined.");
        }

        return $this->parsedArguments[$name] ?? $this->definition->getArguments()[$name]->default;
    }

    public function option(string $name): mixed
    {
        $option = $this->definition->getOptionByName($name) ?? $this->definition->getOptionByShortcut($name);
        
        if ($option === null) {
            throw new ConsoleException("Option '{$name}' is not defined.");
        }

        return $this->parsedOptions[$option->name] ?? $option->default;
    }

    public function hasOption(string $name): bool
    {
        $option = $this->definition->getOptionByName($name) ?? $this->definition->getOptionByShortcut($name);
        
        return $option !== null && isset($this->explicitOptions[$option->name]);
    }

    /**
     * @param array<int, string> $argv
     */
    private function parse(array $argv): void
    {
        $tokens = $argv;
        $argumentIndex = 0;
        $arguments = $this->definition->getArguments();
        $options = $this->definition->getOptions();

        while ($tokens !== []) {
            $token = array_shift($tokens);

            if (str_starts_with($token, '--')) {
                $this->parseLongOption($token, $tokens, $options);
            } elseif (str_starts_with($token, '-')) {
                $this->parseShortOption($token, $tokens, $options);
            } else {
                if ($argumentIndex < count($arguments)) {
                    $argumentName = array_keys($arguments)[$argumentIndex];
                    $this->parsedArguments[$argumentName] = $token;
                    $argumentIndex++;
                }
            }
        }

        $this->validateArguments($arguments);
    }

    /**
     * @param array<int, string> $tokens
     * @param array<string, InputOption> $options
     */
    private function parseLongOption(string $token, array &$tokens, array $options): void
    {
        $name = substr($token, 2);
        
        if (str_contains($name, '=')) {
            [$name, $value] = explode('=', $name, 2);
            $this->setOption($name, $value, $options);
        } else {
            $option = $options[$name] ?? null;
            
            if ($option === null) {
                throw new ConsoleException("Option '--{$name}' is not defined.");
            }

            if ($option->mode === InputOption::VALUE_NONE) {
                $this->setOption($name, true, $options);
            } elseif ($option->mode === InputOption::VALUE_REQUIRED) {
                if ($tokens === []) {
                    throw new ConsoleException("Option '--{$name}' requires a value.");
                }
                $value = array_shift($tokens);
                $this->setOption($name, $value, $options);
            } elseif ($option->mode === InputOption::VALUE_OPTIONAL) {
                if ($tokens !== [] && !str_starts_with($tokens[0], '-')) {
                    $value = array_shift($tokens);
                    $this->setOption($name, $value, $options);
                } else {
                    $this->setOption($name, $option->default, $options);
                }
            }
        }
    }

    /**
     * @param array<int, string> $tokens
     * @param array<string, InputOption> $options
     */
    private function parseShortOption(string $token, array &$tokens, array $options): void
    {
        $shortcut = substr($token, 1);
        $option = $this->definition->getOptionByShortcut($shortcut);
        
        if ($option === null) {
            throw new ConsoleException("Option '-{$shortcut}' is not defined.");
        }

        if ($option->mode === InputOption::VALUE_NONE) {
            $this->setOption($option->name, true, $options);
        } elseif ($option->mode === InputOption::VALUE_REQUIRED) {
            if ($tokens === []) {
                throw new ConsoleException("Option '-{$shortcut}' requires a value.");
            }
            $value = array_shift($tokens);
            $this->setOption($option->name, $value, $options);
        } elseif ($option->mode === InputOption::VALUE_OPTIONAL) {
            if ($tokens !== [] && !str_starts_with($tokens[0], '-')) {
                $value = array_shift($tokens);
                $this->setOption($option->name, $value, $options);
            } else {
                $this->setOption($option->name, $option->default, $options);
            }
        }
    }

    /**
     * @param array<string, InputOption> $options
     */
    private function setOption(string $name, mixed $value, array $options): void
    {
        $option = $options[$name] ?? null;
        
        if ($option === null) {
            throw new ConsoleException("Option '{$name}' is not defined.");
        }

        $this->parsedOptions[$name] = $value;
        $this->explicitOptions[$name] = true;
    }

    /**
     * @param array<string, InputArgument> $arguments
     */
    private function validateArguments(array $arguments): void
    {
        foreach ($arguments as $argument) {
            if ($argument->mode === InputArgument::REQUIRED && !isset($this->parsedArguments[$argument->name])) {
                throw new ConsoleException("Argument '{$argument->name}' is required.");
            }
        }
    }
}
<?php

declare(strict_types=1);

namespace Docile\Console;

use Docile\Console\Attribute\AsCommand;
use Docile\Console\Exception\ConsoleException;
use ReflectionClass;

abstract class Command
{
    public function configure(InputDefinition $definition): void
    {
    }

    abstract protected function handle(Input $input, Output $output): int;

    final public function run(Input $input, Output $output): int
    {
        return $this->handle($input, $output);
    }

    final public static function getName(): string
    {
        $reflection = new ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(AsCommand::class);

        if ($attributes === []) {
            throw new ConsoleException(sprintf(
                'Command %s must have #[AsCommand] attribute',
                static::class
            ));
        }

        return $attributes[0]->newInstance()->name;
    }

    final public static function getDescription(): string
    {
        $reflection = new ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(AsCommand::class);

        if ($attributes === []) {
            throw new ConsoleException(sprintf(
                'Command %s must have #[AsCommand] attribute',
                static::class
            ));
        }

        return $attributes[0]->newInstance()->description;
    }
}
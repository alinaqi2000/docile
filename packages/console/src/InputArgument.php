<?php

declare(strict_types=1);

namespace Docile\Console;

final readonly class InputArgument
{
    public const int REQUIRED = 1;
    public const int OPTIONAL = 2;
    public const int IS_ARRAY = 4;

    public function __construct(
        public readonly string $name,
        public readonly int $mode = self::REQUIRED,
        public readonly string $description = '',
        public readonly mixed $default = null,
    ) {}
}
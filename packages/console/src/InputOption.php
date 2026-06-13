<?php

declare(strict_types=1);

namespace Docile\Console;

final readonly class InputOption
{
    public const int VALUE_NONE = 1;
    public const int VALUE_REQUIRED = 2;
    public const int VALUE_OPTIONAL = 4;
    public const int VALUE_IS_ARRAY = 8;

    public function __construct(
        public readonly string $name,
        public readonly ?string $shortcut = null,
        public readonly int $mode = self::VALUE_NONE,
        public readonly string $description = '',
        public readonly mixed $default = null,
    ) {}
}
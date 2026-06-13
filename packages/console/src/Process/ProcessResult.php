<?php

declare(strict_types=1);

namespace Docile\Console\Process;

final readonly class ProcessResult
{
    public function __construct(
        public readonly string $label,
        /** @var list<string> */
        public readonly array $command,
        public readonly int $exitCode,
        public readonly string $stdout,
        public readonly string $stderr,
        public readonly float $duration,
    ) {}

    public function successful(): bool
    {
        return $this->exitCode === 0;
    }
}
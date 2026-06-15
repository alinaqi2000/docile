<?php

declare(strict_types=1);

namespace Docile\Console\Process;

final class ProcessPoolResult
{
    /** @var array<ProcessResult> */
    private array $results;

    /** @param array<ProcessResult> $results */
    public function __construct(array $results, private readonly float $totalDuration)
    {
        $this->results = $results;
    }

    /** @return array<ProcessResult> */
    public function results(): array
    {
        return $this->results;
    }

    public function successful(): bool
    {
        foreach ($this->results as $result) {
            if (!$result->successful()) {
                return false;
            }
        }

        return true;
    }

    /** @return array<ProcessResult> */
    public function failed(): array
    {
        return array_values(array_filter($this->results, static fn(ProcessResult $result) => !$result->successful()));
    }

    public function duration(): float
    {
        return $this->totalDuration;
    }
}
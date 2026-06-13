<?php

declare(strict_types=1);

namespace Docile\Testing\Console;

use PHPUnit\Framework\Assert;

final class TestOutput
{
    /** @var list<string> */
    private array $lines = [];

    public function write(string $message, bool $newline = false): void
    {
        $this->lines[] = $message;
        if ($newline) {
            $this->lines[] = '';
        }
    }

    public function writeln(string $message): void
    {
        $this->write($message, true);
    }

    public function info(string $message): void
    {
        $this->writeln("[INFO] {$message}");
    }

    public function warn(string $message): void
    {
        $this->writeln("[WARN] {$message}");
    }

    public function error(string $message): void
    {
        $this->writeln("[ERROR] {$message}");
    }

    public function success(string $message): void
    {
        $this->writeln("[OK] {$message}");
    }

    public function line(string $message): void
    {
        $this->writeln($message);
    }

    /**
     * @param list<string> $headers
     * @param list<list<string>> $rows
     */
    public function table(array $headers, array $rows): void
    {
        if ($headers === [] || $rows === []) {
            return;
        }

        $allRows = [$headers, ...$rows];
        $columnWidths = [];

        foreach ($allRows as $row) {
            foreach ($row as $i => $cell) {
                $cellLength = strlen($cell);
                $columnWidths[$i] = max($columnWidths[$i] ?? 0, $cellLength);
            }
        }

        $separator = '';
        foreach ($columnWidths as $width) {
            $separator .= '+' . str_repeat('-', $width + 2);
        }
        $separator .= '+';

        $this->writeln($separator);

        $line = '|';
        foreach ($headers as $i => $cell) {
            $line .= ' ' . str_pad($cell, $columnWidths[$i]) . ' |';
        }
        $this->writeln($line);
        $this->writeln($separator);

        foreach ($rows as $dataRow) {
            $line = '|';
            foreach ($dataRow as $i => $cell) {
                $line .= ' ' . str_pad($cell, $columnWidths[$i]) . ' |';
            }
            $this->writeln($line);
        }

        $this->writeln($separator);
    }

    public function isDecorated(): bool
    {
        return false;
    }

    /**
     * @return list<string>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getOutput(): string
    {
        return implode("\n", $this->lines);
    }

    public function assertContains(string $needle): void
    {
        $output = $this->getOutput();

        if (str_contains($output, $needle) === false) {
            Assert::fail("Output does not contain '{$needle}'");
        }
    }

    public function assertEmpty(): void
    {
        if ($this->lines !== []) {
            Assert::fail('Output is not empty');
        }
    }
}

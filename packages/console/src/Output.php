<?php

declare(strict_types=1);

namespace Docile\Console;

final class Output
{
    private mixed $stream;

    public function __construct(mixed $stream = null)
    {
        $this->stream = $stream ?? fopen('php://stdout', 'w');
    }

    public function write(string $message, bool $newline = false): void
    {
        /** @var resource $stream */
        $stream = $this->stream;
        fwrite($stream, $message);
        if ($newline) {
            fwrite($stream, "\n");
        }
    }

    public function writeln(string $message): void
    {
        $this->write($message, true);
    }

    public function info(string $message): void
    {
        if ($this->isDecorated()) {
            $this->writeln("\033[32m[INFO]\033[0m {$message}");
        } else {
            $this->writeln("[INFO] {$message}");
        }
    }

    public function warn(string $message): void
    {
        if ($this->isDecorated()) {
            $this->writeln("\033[33m[WARN]\033[0m {$message}");
        } else {
            $this->writeln("[WARN] {$message}");
        }
    }

    public function error(string $message): void
    {
        if ($this->isDecorated()) {
            $this->writeln("\033[31m[ERROR]\033[0m {$message}");
        } else {
            $this->writeln("[ERROR] {$message}");
        }
    }

    public function success(string $message): void
    {
        if ($this->isDecorated()) {
            $this->writeln("\033[32m[OK]\033[0m {$message}");
        } else {
            $this->writeln("[OK] {$message}");
        }
    }

    public function line(string $message): void
    {
        $this->writeln($message);
    }

    /**
     * @param array<string> $headers
     * @param array<array<string>> $rows
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
                $cellLength = strlen((string) $cell);
                $columnWidths[$i] = max($columnWidths[$i] ?? 0, $cellLength);
            }
        }

        $separator = '';
        foreach ($columnWidths as $width) {
            $separator .= '+' . str_repeat('-', $width + 2);
        }
        $separator .= '+';

        $this->writeln($separator);

        $headerRow = $headers;
        $line = '|';
        foreach ($headerRow as $i => $cell) {
            $line .= ' ' . str_pad((string) $cell, $columnWidths[$i]) . ' |';
        }
        $this->writeln($line);
        $this->writeln($separator);

        foreach ($rows as $dataRow) {
            $line = '|';
            foreach ($dataRow as $i => $cell) {
                $line .= ' ' . str_pad((string) $cell, $columnWidths[$i]) . ' |';
            }
            $this->writeln($line);
        }

        $this->writeln($separator);
    }

    public function isDecorated(): bool
    {
        if (isset($_SERVER['NO_COLOR']) && $_SERVER['NO_COLOR'] !== '') {
            return false;
        }

        if (function_exists('stream_isatty')) {
            /** @var resource $stream */
            $stream = $this->stream;
            return stream_isatty($stream);
        }

        if (function_exists('posix_isatty')) {
            /** @var resource $stream */
            $stream = $this->stream;
            return posix_isatty($stream);
        }

        return false;
    }
}
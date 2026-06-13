<?php

declare(strict_types=1);

namespace Docile\Console\Tests\Process;

use Docile\Console\Process\ProcessResult;
use PHPUnit\Framework\TestCase;

final class ProcessResultTest extends TestCase
{
    public function testProcessResultCreation(): void
    {
        $result = new ProcessResult(
            label: 'test',
            command: ['php', '-r', 'echo "hello";'],
            exitCode: 0,
            stdout: 'hello',
            stderr: '',
            duration: 0.1,
        );

        $this->assertSame('test', $result->label);
        $this->assertSame(['php', '-r', 'echo "hello";'], $result->command);
        $this->assertSame(0, $result->exitCode);
        $this->assertSame('hello', $result->stdout);
        $this->assertSame('', $result->stderr);
        $this->assertSame(0.1, $result->duration);
    }

    public function testSuccessfulReturnsTrueForZeroExitCode(): void
    {
        $result = new ProcessResult(
            label: 'test',
            command: ['echo', 'hello'],
            exitCode: 0,
            stdout: 'hello',
            stderr: '',
            duration: 0.1,
        );

        $this->assertTrue($result->successful());
    }

    public function testSuccessfulReturnsFalseForNonZeroExitCode(): void
    {
        $result = new ProcessResult(
            label: 'test',
            command: ['php', '-r', 'exit(1);'],
            exitCode: 1,
            stdout: '',
            stderr: '',
            duration: 0.1,
        );

        $this->assertFalse($result->successful());
    }

    public function testSuccessfulReturnsFalseForNegativeExitCode(): void
    {
        $result = new ProcessResult(
            label: 'test',
            command: ['php', '-r', 'exit(-1);'],
            exitCode: -1,
            stdout: '',
            stderr: '',
            duration: 0.1,
        );

        $this->assertFalse($result->successful());
    }
}
<?php

declare(strict_types=1);

namespace Docile\Console\Tests\Process;

use Docile\Console\Process\ProcessPoolResult;
use Docile\Console\Process\ProcessResult;
use PHPUnit\Framework\TestCase;

final class ProcessPoolResultTest extends TestCase
{
    public function testProcessPoolResultCreation(): void
    {
        $results = [
            new ProcessResult('test1', ['echo', 'hello'], 0, 'hello', '', 0.1),
            new ProcessResult('test2', ['echo', 'world'], 0, 'world', '', 0.2),
        ];

        $poolResult = new ProcessPoolResult($results, 0.3);

        $this->assertSame($results, $poolResult->results());
        $this->assertSame(0.3, $poolResult->duration());
    }

    public function testSuccessfulReturnsTrueWhenAllProcessesSucceed(): void
    {
        $results = [
            new ProcessResult('test1', ['echo', 'hello'], 0, 'hello', '', 0.1),
            new ProcessResult('test2', ['echo', 'world'], 0, 'world', '', 0.2),
        ];

        $poolResult = new ProcessPoolResult($results, 0.3);

        $this->assertTrue($poolResult->successful());
    }

    public function testSuccessfulReturnsFalseWhenAnyProcessFails(): void
    {
        $results = [
            new ProcessResult('test1', ['echo', 'hello'], 0, 'hello', '', 0.1),
            new ProcessResult('test2', ['php', '-r', 'exit(1);'], 1, '', '', 0.2),
        ];

        $poolResult = new ProcessPoolResult($results, 0.3);

        $this->assertFalse($poolResult->successful());
    }

    public function testFailedReturnsEmptyArrayWhenAllProcessesSucceed(): void
    {
        $results = [
            new ProcessResult('test1', ['echo', 'hello'], 0, 'hello', '', 0.1),
            new ProcessResult('test2', ['echo', 'world'], 0, 'world', '', 0.2),
        ];

        $poolResult = new ProcessPoolResult($results, 0.3);

        $this->assertEmpty($poolResult->failed());
    }

    public function testFailedReturnsFailedProcesses(): void
    {
        $failedResult = new ProcessResult('test2', ['php', '-r', 'exit(1);'], 1, '', '', 0.2);
        $results = [
            new ProcessResult('test1', ['echo', 'hello'], 0, 'hello', '', 0.1),
            $failedResult,
        ];

        $poolResult = new ProcessPoolResult($results, 0.3);

        $failed = $poolResult->failed();
        $this->assertCount(1, $failed);
        $this->assertSame($failedResult, $failed[0]);
    }

    public function testFailedReturnsMultipleFailedProcesses(): void
    {
        $failedResult1 = new ProcessResult('test2', ['php', '-r', 'exit(1);'], 1, '', '', 0.2);
        $failedResult2 = new ProcessResult('test3', ['php', '-r', 'exit(2);'], 2, '', '', 0.3);
        $results = [
            new ProcessResult('test1', ['echo', 'hello'], 0, 'hello', '', 0.1),
            $failedResult1,
            $failedResult2,
        ];

        $poolResult = new ProcessPoolResult($results, 0.4);

        $failed = $poolResult->failed();
        $this->assertCount(2, $failed);
        $this->assertSame($failedResult1, $failed[0]);
        $this->assertSame($failedResult2, $failed[1]);
    }

    public function testEmptyProcessPoolResult(): void
    {
        $poolResult = new ProcessPoolResult([], 0.0);

        $this->assertEmpty($poolResult->results());
        $this->assertTrue($poolResult->successful());
        $this->assertEmpty($poolResult->failed());
        $this->assertSame(0.0, $poolResult->duration());
    }
}
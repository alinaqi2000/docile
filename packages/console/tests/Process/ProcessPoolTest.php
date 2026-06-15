<?php

declare(strict_types=1);

namespace Docile\Console\Tests\Process;

use Docile\Console\Process\ProcessPool;
use Docile\Console\Process\ProcessResult;
use PHPUnit\Framework\TestCase;

final class ProcessPoolTest extends TestCase
{
    public function testAddProcess(): void
    {
        $pool = new ProcessPool();
        
        $returnedPool = $pool->add(['echo', 'hello'], 'test');
        
        $this->assertSame($pool, $returnedPool);
    }

    public function testRunEmptyPool(): void
    {
        $pool = new ProcessPool();
        $result = $pool->run();
        
        $this->assertEmpty($result->results());
        $this->assertTrue($result->successful());
        $this->assertSame(0.0, $result->duration());
    }

    public function testRunSingleProcess(): void
    {
        $pool = new ProcessPool();
        $pool->add(['php', '-r', 'echo "hello";'], 'test');
        
        $result = $pool->run();
        
        $this->assertCount(1, $result->results());
        $this->assertTrue($result->successful());
        
        $processResult = $result->results()[0];
        $this->assertSame('test', $processResult->label);
        $this->assertSame(['php', '-r', 'echo "hello";'], $processResult->command);
        $this->assertSame(0, $processResult->exitCode);
        $this->assertSame('hello', $processResult->stdout);
        $this->assertEmpty($processResult->stderr);
        $this->assertTrue($processResult->successful());
    }

    public function testRunMultipleProcesses(): void
    {
        $pool = new ProcessPool();
        $pool->add(['php', '-r', 'echo "hello";'], 'test1');
        $pool->add(['php', '-r', 'echo "world";'], 'test2');
        
        $result = $pool->run();
        
        $this->assertCount(2, $result->results());
        $this->assertTrue($result->successful());
        
        $results = $result->results();
        $labels = array_map(static fn(ProcessResult $r) => $r->label, $results);
        $outputs = array_map(static fn(ProcessResult $r) => trim($r->stdout), $results);
        
        $this->assertContains('test1', $labels);
        $this->assertContains('test2', $labels);
        $this->assertContains('hello', $outputs);
        $this->assertContains('world', $outputs);
    }

    public function testRunWithMaxConcurrency(): void
    {
        $pool = new ProcessPool(2);
        
        for ($i = 1; $i <= 5; $i++) {
            $pool->add(['php', '-r', "echo {$i};"], "test{$i}");
        }
        
        $result = $pool->run();
        
        $this->assertCount(5, $result->results());
        $this->assertTrue($result->successful());
        
        $outputs = array_map(static fn(ProcessResult $r) => trim($r->stdout), $result->results());
        sort($outputs);
        
        $this->assertSame(['1', '2', '3', '4', '5'], $outputs);
    }

    public function testRunWithTimeout(): void
    {
        $pool = new ProcessPool();
        $pool->add(['php', '-r', 'sleep(10);'], 'long-running', 1);
        
        $result = $pool->run();
        
        $this->assertCount(1, $result->results());
        $this->assertFalse($result->successful());
        
        $processResult = $result->results()[0];
        $this->assertSame(1, $processResult->exitCode);
        $this->assertLessThan(2.0, $processResult->duration);
    }

    public function testRunWithFailedProcess(): void
    {
        $pool = new ProcessPool();
        $pool->add(['php', '-r', 'echo "success";'], 'success');
        $pool->add(['php', '-r', 'exit(1);'], 'failure');
        
        $result = $pool->run();
        
        $this->assertCount(2, $result->results());
        $this->assertFalse($result->successful());
        
        $failed = $result->failed();
        $this->assertCount(1, $failed);
        $labels = array_map(static fn($r) => $r->label, $failed);
        $this->assertContains('failure', $labels);
    }

    public function testRunWithOnProgressCallback(): void
    {
        $pool = new ProcessPool();
        $pool->add(['php', '-r', 'echo "hello";'], 'test1');
        $pool->add(['php', '-r', 'echo "world";'], 'test2');
        
        $progressCalls = [];
        $onProgress = function (ProcessResult $result, int $completed, int $total) use (&$progressCalls): void {
            $progressCalls[] = [
                'label' => $result->label,
                'completed' => $completed,
                'total' => $total,
            ];
        };
        
        $result = $pool->run($onProgress);
        
        $this->assertCount(2, $progressCalls);
        $this->assertSame(2, $progressCalls[0]['total']);
        $this->assertSame(1, $progressCalls[0]['completed']);
        $this->assertSame(2, $progressCalls[1]['total']);
        $this->assertSame(2, $progressCalls[1]['completed']);
    }

    public function testRunProcessWithStderr(): void
    {
        $pool = new ProcessPool();
        $pool->add(['php', '-r', 'fwrite(STDERR, "error message");'], 'test');
        
        $result = $pool->run();
        
        $this->assertCount(1, $result->results());
        $this->assertTrue($result->successful());
        
        $processResult = $result->results()[0];
        $this->assertSame('error message', $processResult->stderr);
    }

    public function testRunProcessWithBothStdoutAndStderr(): void
    {
        $pool = new ProcessPool();
        $pool->add(['php', '-r', 'echo "output"; fwrite(STDERR, "error");'], 'test');
        
        $result = $pool->run();
        
        $this->assertCount(1, $result->results());
        $this->assertTrue($result->successful());
        
        $processResult = $result->results()[0];
        $this->assertSame('output', $processResult->stdout);
        $this->assertSame('error', $processResult->stderr);
    }

    public function testDurationIsAccuratelyMeasured(): void
    {
        $pool = new ProcessPool();
        $pool->add(['php', '-r', 'usleep(50000);'], 'test');
        
        $result = $pool->run();
        
        $this->assertGreaterThanOrEqual(0.05, $result->duration());
        $this->assertLessThan(0.2, $result->duration());
    }
}
<?php

declare(strict_types=1);

namespace Docile\Console\Process;

use Closure;

final class ProcessPool
{
    /** @var array<array{command: array<int, string>, label: string, timeout: ?int}> */
    private array $processes = [];

    public function __construct(private readonly int $maxConcurrency = 8) {}

    /**
     * @param list<string> $command
     */
    public function add(
        array $command,
        string $label = '',
        ?int $timeout = null,
    ): static {
        $this->processes[] = [
            'command' => $command,
            'label' => $label,
            'timeout' => $timeout,
        ];

        return $this;
    }

    /**
     * @param ?Closure(ProcessResult, int, int): void $onProgress
     */
    public function run(?Closure $onProgress = null): ProcessPoolResult
    {
        if ($this->processes === []) {
            return new ProcessPoolResult([], 0.0);
        }

        $startTime = microtime(true);
        $results = [];
        $pending = $this->processes;
        $running = [];
        $completed = 0;

        while ($pending !== [] || $running !== []) {
            while (count($running) < $this->maxConcurrency && $pending !== []) {
                $spec = array_shift($pending);
                $process = $this->startProcess(array_values($spec['command']), $spec['timeout']);
                $running[] = [
                    'spec' => $spec,
                    'process' => $process,
                    'start_time' => microtime(true),
                    'stdout' => '',
                    'stderr' => '',
                ];
            }

            $read = [];
            $write = [];
            $except = [];

            foreach ($running as $i => $proc) {
                $read[] = $proc['process'][1];
                $read[] = $proc['process'][2];
            }

            if ($read !== []) {
                stream_select($read, $write, $except, 1, 0);
            }

            $toRemove = [];
            foreach ($running as $i => $proc) {
                $updated = false;

                if (in_array($proc['process'][1], $read, true) && is_resource($proc['process'][1])) {
                    $data = fread($proc['process'][1], 8192);
                    if ($data !== false && $data !== '') {
                        $running[$i]['stdout'] .= $data;
                        $updated = true;
                    }
                }

                if (in_array($proc['process'][2], $read, true) && is_resource($proc['process'][2])) {
                    $data = fread($proc['process'][2], 8192);
                    if ($data !== false && $data !== '') {
                        $running[$i]['stderr'] .= $data;
                        $updated = true;
                    }
                }

                $currentTime = microtime(true);
                $duration = $currentTime - $proc['start_time'];

                $timedOut = $proc['spec']['timeout'] !== null && $duration > $proc['spec']['timeout'];

                $status = null;
                if (is_resource($proc['process'][0])) {
                    $status = proc_get_status($proc['process'][0]);
                }

                if ($status === null || !$status['running'] || $timedOut) {
                    if ($timedOut) {
                        if (is_resource($proc['process'][0])) {
                            proc_terminate($proc['process'][0]);
                        }
                        $exitCode = 1;
                    } else {
                        $exitCode = $status !== null ? $status['exitcode'] : 1;
                    }

                    if (is_resource($proc['process'][1])) {
                        fclose($proc['process'][1]);
                    }
                    if (is_resource($proc['process'][2])) {
                        fclose($proc['process'][2]);
                    }
                    if (is_resource($proc['process'][0])) {
                        proc_close($proc['process'][0]);
                    }

                    $result = new ProcessResult(
                        label: $proc['spec']['label'],
                        command: array_values($proc['spec']['command']),
                        exitCode: $exitCode,
                        stdout: $running[$i]['stdout'],
                        stderr: $running[$i]['stderr'],
                        duration: $duration,
                    );

                    $results[] = $result;
                    $completed++;

                    if ($onProgress !== null) {
                        $onProgress($result, $completed, count($this->processes));
                    }

                    $toRemove[] = $i;
                }
            }

            foreach ($toRemove as $i) {
                unset($running[$i]);
            }

            $running = array_values($running);
            usleep(10000);
        }

        $totalDuration = microtime(true) - $startTime;

        return new ProcessPoolResult($results, $totalDuration);
    }

    /**
     * @param list<string> $command
     * @return array{resource, resource, resource}
     */
    private function startProcess(array $command, ?int $timeout): array
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start process.');
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        fclose($pipes[0]);

        return [
            $process,
            $pipes[1],
            $pipes[2],
        ];
    }
}
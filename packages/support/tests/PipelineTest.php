<?php

declare(strict_types=1);

namespace Docile\Support\Tests;

use Closure;
use Docile\Support\Exception\InvalidPipeException;
use Docile\Support\Pipeline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Pipeline::class)]
#[CoversClass(InvalidPipeException::class)]
final class PipelineTest extends TestCase
{
    // ---------------------------------------------------------------- then

    public function testThenWithNoPipes(): void
    {
        $result = (new Pipeline())
            ->send('hello')
            ->through([])
            ->then(static function (mixed $v): mixed {
                self::assertIsString($v);

                return strtoupper($v);
            });

        self::assertSame('HELLO', $result);
    }

    public function testThenWithClosurePipes(): void
    {
        $result = (new Pipeline())
            ->send(0)
            ->through([
                static function (mixed $v, Closure $next): mixed {
                    self::assertIsInt($v);

                    return $next($v + 1);
                },
                static function (mixed $v, Closure $next): mixed {
                    self::assertIsInt($v);

                    return $next($v * 10);
                },
            ])
            ->then(fn (mixed $v): mixed => $v);

        self::assertSame(10, $result);
    }

    public function testThenPipesExecuteInOrder(): void
    {
        $log = [];

        (new Pipeline())
            ->send('payload')
            ->through([
                function (mixed $v, Closure $next) use (&$log): mixed {
                    $log[] = 'pipe1-before';
                    $result = $next($v);
                    $log[] = 'pipe1-after';

                    return $result;
                },
                function (mixed $v, Closure $next) use (&$log): mixed {
                    $log[] = 'pipe2-before';
                    $result = $next($v);
                    $log[] = 'pipe2-after';

                    return $result;
                },
            ])
            ->then(function (mixed $v) use (&$log): mixed {
                $log[] = 'destination';

                return $v;
            });

        self::assertSame(['pipe1-before', 'pipe2-before', 'destination', 'pipe2-after', 'pipe1-after'], $log);
    }

    // ---------------------------------------------------------------- then with class-string pipes

    public function testThenWithClassStringPipe(): void
    {
        $result = (new Pipeline())
            ->send('hello')
            ->through([UpperCasePipe::class])
            ->then(fn (mixed $v): mixed => $v);

        self::assertSame('HELLO', $result);
    }

    public function testThenWithMixedPipes(): void
    {
        $result = (new Pipeline())
            ->send('hello')
            ->through([
                UpperCasePipe::class,
                static function (mixed $v, Closure $next): mixed {
                    self::assertIsString($v);

                    return $next('!' . $v . '!');
                },
            ])
            ->then(fn (mixed $v): mixed => $v);

        self::assertSame('!HELLO!', $result);
    }

    // ---------------------------------------------------------------- thenReturn

    public function testThenReturn(): void
    {
        $result = (new Pipeline())
            ->send(42)
            ->through([
                static function (mixed $v, Closure $next): mixed {
                    self::assertIsInt($v);

                    return $next($v + 8);
                },
            ])
            ->thenReturn();

        self::assertSame(50, $result);
    }

    public function testThenReturnNoPipes(): void
    {
        $result = (new Pipeline())
            ->send('unchanged')
            ->through([])
            ->thenReturn();

        self::assertSame('unchanged', $result);
    }

    // ---------------------------------------------------------------- immutability

    public function testSendReturnsNewInstance(): void
    {
        $pipeline = new Pipeline();
        $withPayload = $pipeline->send('test');

        self::assertNotSame($pipeline, $withPayload);
    }

    public function testThroughReturnsNewInstance(): void
    {
        $pipeline = (new Pipeline())->send('test');
        $withPipes = $pipeline->through([]);

        self::assertNotSame($pipeline, $withPipes);
    }

    // ---------------------------------------------------------------- error cases

    public function testThroughRejectsInvalidPipes(): void
    {
        $this->expectException(InvalidPipeException::class);

        (new Pipeline())
            ->send('x')
            ->through([42]); // @phpstan-ignore argument.type
    }

    public function testThenThrowsOnClassStringWithoutHandle(): void
    {
        $this->expectException(InvalidPipeException::class);

        (new Pipeline())
            ->send('x')
            ->through([NoHandlePipe::class])
            ->thenReturn();
    }
}

// ---------------------------------------------------------------------------
// Inline fixture pipes (defined in test file to keep fixtures local)
// ---------------------------------------------------------------------------

final class UpperCasePipe
{
    /** @param Closure(mixed): mixed $next */
    public function handle(mixed $payload, Closure $next): mixed
    {
        assert(is_string($payload));

        return $next(strtoupper($payload));
    }
}

final class NoHandlePipe
{
    // intentionally missing handle()
}

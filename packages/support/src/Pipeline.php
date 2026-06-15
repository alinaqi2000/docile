<?php

declare(strict_types=1);

namespace Docile\Support;

use Closure;
use Docile\Support\Exception\InvalidPipeException;

use function array_reduce;
use function array_reverse;
use function class_exists;
use function is_string;
use function method_exists;

/**
 * Middleware-style pipeline: passes a payload through a series of pipes.
 *
 * Each pipe is either:
 *   - A `Closure(mixed $payload, Closure $next): mixed`
 *   - A class-string whose instances have a `handle(mixed $payload, Closure $next): mixed` method.
 */
final class Pipeline
{
    /** @var mixed */
    private mixed $payload = null;

    /** @var list<Closure(mixed, Closure(mixed): mixed): mixed|class-string> */
    private array $pipes = [];

    /**
     * Set the payload to be sent through the pipeline.
     */
    public function send(mixed $payload): static
    {
        $clone = clone $this;
        $clone->payload = $payload;

        return $clone;
    }

    /**
     * Set the pipes to process the payload through.
     *
     * @param iterable<Closure(mixed, Closure(mixed): mixed): mixed|class-string> $pipes
     */
    public function through(iterable $pipes): static
    {
        $clone = clone $this;
        $clone->pipes = [];

        foreach ($pipes as $pipe) {
            if (!($pipe instanceof Closure) && !is_string($pipe)) {
                throw InvalidPipeException::forPipe($pipe);
            }

            $clone->pipes[] = $pipe;
        }

        return $clone;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param Closure(mixed): mixed $destination
     */
    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->buildLayer(...),
            $destination,
        );

        return $pipeline($this->payload);
    }

    /**
     * Run the pipeline and return the payload at the end.
     */
    public function thenReturn(): mixed
    {
        return $this->then(static fn (mixed $passable): mixed => $passable);
    }

    /**
     * Build a single pipeline layer that wraps the next layer.
     *
     * @param Closure(mixed): mixed $next
     * @param Closure(mixed, Closure(mixed): mixed): mixed|class-string $pipe
     *
     * @return Closure(mixed): mixed
     */
    private function buildLayer(Closure $next, mixed $pipe): Closure
    {
        return function (mixed $passable) use ($pipe, $next): mixed {
            if ($pipe instanceof Closure) {
                return $pipe($passable, $next);
            }

            if (!is_string($pipe)) {
                throw InvalidPipeException::forPipe($pipe);
            }

            if (!class_exists($pipe) || !method_exists($pipe, 'handle')) {
                throw InvalidPipeException::missingHandle($pipe);
            }

            $instance = new $pipe();

            return $instance->handle($passable, $next);
        };
    }
}

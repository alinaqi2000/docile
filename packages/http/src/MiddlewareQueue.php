<?php

declare(strict_types=1);

namespace Docile\Http;

use Psr\Http\Server\MiddlewareInterface;

final class MiddlewareQueue
{
    /** @param array<int, string|MiddlewareInterface> $middleware */
    public function __construct(
        private readonly array $middleware = []
    ) {}

    /** @param string|MiddlewareInterface $middleware */
    public function append(string|MiddlewareInterface $middleware): self
    {
        return new self([...$this->middleware, $middleware]);
    }

    /** @param string|MiddlewareInterface $middleware */
    public function prepend(string|MiddlewareInterface $middleware): self
    {
        return new self([$middleware, ...$this->middleware]);
    }

    /** @return array<int, string|MiddlewareInterface> */
    public function all(): array
    {
        return $this->middleware;
    }
}
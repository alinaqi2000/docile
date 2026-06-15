<?php

declare(strict_types=1);

namespace Docile\Bus\Tests\Fixtures;

use Docile\Bus\MiddlewareInterface;

final class TracingMiddleware implements MiddlewareInterface
{
    /**
     * @param list<string> $trace
     */
    public function __construct(
        private array &$trace,
        private readonly string $name,
    ) {}

    public function handle(object $message, callable $next): mixed
    {
        $this->trace[] = $this->name . ':before';
        $result = $next($message);
        $this->trace[] = $this->name . ':after';

        return $result;
    }
}

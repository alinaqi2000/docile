<?php

declare(strict_types=1);

namespace Docile\Bus\Middleware;

use Closure;
use Docile\Bus\MiddlewareInterface;

use function get_class;

/**
 * Logs the message class name before and after handling.
 */
final readonly class LoggingMiddleware implements MiddlewareInterface
{
    /**
     * @param Closure(string): void $logger
     */
    public function __construct(private Closure $logger) {}

    public function handle(object $message, callable $next): mixed
    {
        ($this->logger)('Dispatching: ' . get_class($message));

        $result = $next($message);

        ($this->logger)('Handled: ' . get_class($message));

        return $result;
    }
}

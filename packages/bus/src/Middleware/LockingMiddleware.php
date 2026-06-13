<?php

declare(strict_types=1);

namespace Docile\Bus\Middleware;

use Docile\Bus\Exception\ReentrantDispatchException;
use Docile\Bus\MiddlewareInterface;

use function sprintf;

/**
 * Prevents the same message class from being dispatched re-entrantly.
 */
final class LockingMiddleware implements MiddlewareInterface
{
    /**
     * @var array<class-string, true>
     */
    private array $locked = [];

    public function handle(object $message, callable $next): mixed
    {
        $class = $message::class;

        if (isset($this->locked[$class])) {
            throw new ReentrantDispatchException(
                sprintf('Message "%s" is already being handled and cannot be dispatched re-entrantly.', $class),
            );
        }

        $this->locked[$class] = true;

        try {
            return $next($message);
        } finally {
            unset($this->locked[$class]);
        }
    }
}

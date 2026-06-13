<?php

declare(strict_types=1);

namespace Docile\Bus;

/**
 * Default message bus implementation with middleware pipeline support.
 *
 * Builds a pipeline: middleware[0] → middleware[1] → … → handler invocation.
 */
final readonly class MessageBus implements MessageBusInterface
{
    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        private HandlerLocatorInterface $locator,
        private array $middleware = [],
    ) {}

    public function dispatch(object $message): mixed
    {
        $handler = $this->locator->getHandler($message);

        $pipeline = static fn(object $msg): mixed => $handler($msg);

        foreach (array_reverse($this->middleware) as $mw) {
            $next = $pipeline;
            $pipeline = static fn(object $msg): mixed => $mw->handle($msg, $next);
        }

        return $pipeline($message);
    }
}

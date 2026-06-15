<?php

declare(strict_types=1);

namespace Docile\Bus;

use Docile\Bus\Exception\HandlerNotFoundException;

use function sprintf;

/**
 * Explicit map-based handler locator.
 *
 * Maps message class names to handler callables.
 */
final class MapLocator implements HandlerLocatorInterface
{
    /**
     * @var array<class-string, callable(object): mixed>
     */
    private array $map;

    /**
     * @param array<class-string, callable(object): mixed> $map
     */
    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

    /**
     * @param class-string $messageClass
     * @param callable(object): mixed $handler
     */
    public function register(string $messageClass, callable $handler): void
    {
        $this->map[$messageClass] = $handler;
    }

    /**
     * @return callable(object): mixed
     */
    public function getHandler(object $message): callable
    {
        $class = $message::class;

        if (!isset($this->map[$class])) {
            throw new HandlerNotFoundException(
                sprintf('No handler registered for message "%s".', $class),
            );
        }

        return $this->map[$class];
    }
}

<?php

declare(strict_types=1);

namespace Docile\Container\Exception;

use function sprintf;

/**
 * Thrown when a circular dependency is detected while autowiring constructor arguments.
 */
final class CircularDependencyException extends ContainerException
{
    /**
     * @param list<string> $stack the build stack, from the first class down to the cycle
     */
    public static function detected(string $concrete, array $stack): self
    {
        $path = implode(' -> ', [...$stack, $concrete]);

        return new self(sprintf('Circular dependency detected while resolving [%s]: %s.', $concrete, $path));
    }
}

<?php

declare(strict_types=1);

namespace Docile\Container\Exception;

use ReflectionParameter;

use function sprintf;

/**
 * Thrown when the container is unable to build a concrete instance — e.g. an abstract/interface
 * with no binding, a non-instantiable class, or an unresolvable primitive constructor argument.
 */
final class BindingResolutionException extends ContainerException
{
    public static function notInstantiable(string $concrete): self
    {
        return new self(sprintf('Target [%s] is not instantiable.', $concrete));
    }

    public static function notFound(string $abstract): self
    {
        return new self(sprintf('Target [%s] does not exist and cannot be resolved.', $abstract));
    }

    public static function unresolvablePrimitive(ReflectionParameter $parameter, string $consumer): self
    {
        return new self(sprintf(
            'Unresolvable dependency resolving [$%s] in class [%s]: no type-hint, default value, or binding.',
            $parameter->getName(),
            $consumer,
        ));
    }
}

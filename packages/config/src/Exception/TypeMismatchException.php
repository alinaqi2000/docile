<?php

declare(strict_types=1);

namespace Docile\Config\Exception;

final class TypeMismatchException extends ConfigException
{
    public static function forKey(string $key, string $expectedType, string $actualType): self
    {
        return new self(sprintf(
            'Configuration key [%s] expected type %s, got %s.',
            $key,
            $expectedType,
            $actualType,
        ));
    }
}

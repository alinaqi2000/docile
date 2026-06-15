<?php

declare(strict_types=1);

namespace Docile\Config\Exception;

final class LoaderException extends ConfigException
{
    public static function fileNotFound(string $path): self
    {
        return new self(sprintf('Configuration file not found: [%s].', $path));
    }

    public static function invalidFile(string $path): self
    {
        return new self(sprintf('Configuration file [%s] must return an array.', $path));
    }

    public static function directoryNotFound(string $directory): self
    {
        return new self(sprintf('Configuration directory not found: [%s].', $directory));
    }
}

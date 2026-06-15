<?php

declare(strict_types=1);

namespace Docile\Config\Exception;

final class MissingKeyException extends ConfigException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf('Required configuration key [%s] is missing.', $key));
    }
}

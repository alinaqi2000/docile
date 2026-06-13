<?php

declare(strict_types=1);

namespace Docile\Config;

use Docile\Config\Exception\LoaderException;

use function is_array;
use function is_file;

/**
 * Loads a single PHP file that must return an array.
 */
final class PhpFileLoader implements LoaderInterface
{
    public function __construct(private readonly string $path) {}

    /**
     * @return array<string, mixed>
     *
     * @throws LoaderException If the file does not exist or does not return an array.
     */
    public function load(): array
    {
        if (!is_file($this->path)) {
            throw LoaderException::fileNotFound($this->path);
        }

        /** @var mixed $data */
        $data = require $this->path;

        if (!is_array($data)) {
            throw LoaderException::invalidFile($this->path);
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}

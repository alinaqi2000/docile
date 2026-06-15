<?php

declare(strict_types=1);

namespace Docile\Config;

use Docile\Config\Exception\LoaderException;

use function glob;
use function is_array;
use function is_dir;
use function pathinfo;
use function rtrim;

use const GLOB_NOSORT;
use const PATHINFO_FILENAME;

/**
 * Loads all *.php files from a directory, using the filename (without extension)
 * as the top-level config key.
 *
 * Example: config/app.php → ['app' => [...file contents...]]
 */
final class DirectoryLoader implements LoaderInterface
{
    public function __construct(private readonly string $directory) {}

    /**
     * @return array<string, mixed>
     *
     * @throws LoaderException If the directory does not exist.
     */
    public function load(): array
    {
        if (!is_dir($this->directory)) {
            throw LoaderException::directoryNotFound($this->directory);
        }

        $pattern = rtrim($this->directory, '/\\') . '/*.php';
        $files = glob($pattern, GLOB_NOSORT);

        if ($files === false) {
            $files = [];
        }

        $result = [];

        foreach ($files as $file) {
            /** @var mixed $data */
            $data = require $file;

            if (!is_array($data)) {
                throw LoaderException::invalidFile($file);
            }

            /** @var array<string, mixed> $data */
            $key = pathinfo($file, PATHINFO_FILENAME);
            $result[$key] = $data;
        }

        return $result;
    }
}

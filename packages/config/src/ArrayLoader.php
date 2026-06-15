<?php

declare(strict_types=1);

namespace Docile\Config;

/**
 * Wraps a plain array as a loader — useful for testing and bootstrapping.
 */
final class ArrayLoader implements LoaderInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private readonly array $data) {}

    /**
     * @return array<string, mixed>
     */
    public function load(): array
    {
        return $this->data;
    }
}

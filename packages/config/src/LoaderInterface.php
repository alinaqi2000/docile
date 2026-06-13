<?php

declare(strict_types=1);

namespace Docile\Config;

interface LoaderInterface
{
    /**
     * Returns a fully-nested array of config values.
     *
     * @return array<string, mixed>
     */
    public function load(): array;
}

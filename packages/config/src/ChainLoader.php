<?php

declare(strict_types=1);

namespace Docile\Config;

/**
 * Merges multiple loaders in order — later loaders win on key conflict.
 */
final class ChainLoader implements LoaderInterface
{
    /** @var list<LoaderInterface> */
    private readonly array $loaders;

    public function __construct(LoaderInterface ...$loaders)
    {
        $this->loaders = array_values($loaders);
    }

    /**
     * @return array<string, mixed>
     */
    public function load(): array
    {
        $result = [];

        foreach ($this->loaders as $loader) {
            $result = $this->deepMerge($result, $loader->load());
        }

        return $result;
    }

    /**
     * Deep-merges $override on top of $base. Scalar values in $override win.
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function deepMerge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (
                isset($base[$key])
                && is_array($base[$key])
                && is_array($value)
            ) {
                /** @var array<string, mixed> $baseValue */
                $baseValue = $base[$key];
                /** @var array<string, mixed> $value */
                $base[$key] = $this->deepMerge($baseValue, $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}

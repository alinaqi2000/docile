<?php

declare(strict_types=1);

namespace Docile\Container\Attribute;

use Attribute;

/**
 * Marks a class as a shared (singleton) service: the container resolves it once and reuses the
 * same instance for subsequent lookups, even without an explicit {@see \Docile\Container\Container::singleton()} binding.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Singleton {}

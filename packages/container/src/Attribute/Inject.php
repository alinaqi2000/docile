<?php

declare(strict_types=1);

namespace Docile\Container\Attribute;

use Attribute;

/**
 * Marks a constructor/method parameter to be resolved from the container by an explicit identifier,
 * overriding type-based autowiring.
 *
 * <code>
 * public function __construct(#[Inject('cache.redis')] private CacheInterface $cache) {}
 * </code>
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Inject
{
    public function __construct(public string $id) {}
}

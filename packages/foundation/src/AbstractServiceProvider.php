<?php

declare(strict_types=1);

namespace Docile\Foundation;

use Docile\Container\ContainerInterface;

/**
 * Abstract base for service providers.
 *
 * Provides a default no-op boot() implementation so subclasses only need
 * to override register() (and optionally boot() when needed).
 */
abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    /**
     * Default no-op boot — subclasses override only what they need.
     */
    public function boot(ContainerInterface $container): void {}
}

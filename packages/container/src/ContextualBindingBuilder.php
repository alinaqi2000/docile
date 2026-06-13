<?php

declare(strict_types=1);

namespace Docile\Container;

use Closure;

/**
 * Fluent builder for contextual bindings:
 *
 * <code>
 * $container->when(ReportController::class)
 *           ->needs(StorageInterface::class)
 *           ->give(S3Storage::class);
 * </code>
 */
final class ContextualBindingBuilder
{
    private string $need = '';

    /**
     * @param class-string $concrete
     */
    public function __construct(
        private readonly Container $container,
        private readonly string $concrete,
    ) {}

    /**
     * The abstract/identifier the consumer depends on.
     */
    public function needs(string $abstract): self
    {
        $this->need = $abstract;

        return $this;
    }

    /**
     * The implementation to provide for that dependency in this context.
     *
     * @param Closure(ContainerInterface): mixed|class-string|string $implementation
     */
    public function give(Closure|string $implementation): void
    {
        $this->container->addContextualBinding($this->concrete, $this->need, $implementation);
    }
}

<?php

declare(strict_types=1);

namespace Docile\Container;

use Closure;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Docile's container contract. Extends PSR-11 with binding, autowiring, method injection,
 * contextual bindings, and request scopes.
 */
interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Register a binding. When $concrete is null, $abstract is treated as the concrete class.
     *
     * @param Closure(ContainerInterface, array<string, mixed>): mixed|class-string|null $concrete
     */
    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void;

    /**
     * Register a shared (singleton) binding.
     *
     * @param Closure(ContainerInterface, array<string, mixed>): mixed|class-string|null $concrete
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void;

    /**
     * Register an already-constructed instance as a shared binding.
     *
     * @template T of object
     *
     * @param T $instance
     *
     * @return T
     */
    public function instance(string $abstract, object $instance): object;

    /**
     * Alias an abstract to another identifier.
     */
    public function alias(string $abstract, string $alias): void;

    /**
     * Begin defining a contextual binding: when($consumer)->needs($abstract)->give($concrete).
     *
     * @param class-string $concrete
     */
    public function when(string $concrete): ContextualBindingBuilder;

    /**
     * Resolve the given type, applying optional primitive overrides by parameter name.
     *
     * @param array<string, mixed> $parameters
     */
    public function make(string $abstract, array $parameters = []): mixed;

    /**
     * Call the given callable, injecting its dependencies from the container.
     * The Closure may declare any parameter types; the container resolves them by type.
     *
     * @param Closure(mixed...): mixed|array{0: object|class-string, 1: non-empty-string}|string $callback
     * @param array<string, mixed> $parameters
     */
    public function call(Closure|array|string $callback, array $parameters = []): mixed;

    /**
     * Create a child scope that shares this container's definitions and singletons, but isolates
     * its own per-scope instance overrides (used for request-scoped services in worker runtimes).
     */
    public function scoped(): ContainerInterface;
}

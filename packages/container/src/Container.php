<?php

declare(strict_types=1);

namespace Docile\Container;

use Closure;
use Docile\Container\Attribute\Inject;
use Docile\Container\Attribute\Singleton;
use Docile\Container\Exception\BindingResolutionException;
use Docile\Container\Exception\CircularDependencyException;
use Docile\Container\Exception\ContainerException;
use Docile\Container\Exception\NotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

use function array_key_exists;
use function function_exists;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Docile's autowiring, PSR-11 compatible dependency injection container.
 *
 * Features: type-based autowiring, singleton/transient/instance bindings, aliases, contextual
 * bindings, `#[Inject]`/`#[Singleton]` attributes, method & closure injection, and request scopes.
 */
final class Container implements ContainerInterface
{
    /** @var array<string, array{concrete: Closure(ContainerInterface, array<string, mixed>): mixed|string, shared: bool}> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, string> alias => abstract */
    private array $aliases = [];

    /** @var array<string, array<string, Closure(ContainerInterface): mixed|string>> consumer => (dependency => implementation) */
    private array $contextual = [];

    /** @var array<string, true> classes currently being built (cycle detection) */
    private array $buildStack = [];

    /** @var array<string, bool> */
    private array $singletonAttributeCache = [];

    public function __construct(private readonly ?Container $parent = null) {}

    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        unset($this->instances[$abstract]);
        $this->bindings[$abstract] = ['concrete' => $concrete ?? $abstract, 'shared' => $shared];
    }

    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, object $instance): object
    {
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new ContainerException(sprintf('[%s] cannot be aliased to itself.', $abstract));
        }

        $this->aliases[$alias] = $abstract;
    }

    public function when(string $concrete): ContextualBindingBuilder
    {
        return new ContextualBindingBuilder($this, $concrete);
    }

    /**
     * @internal Use {@see when()} instead.
     *
     * @param Closure(ContainerInterface): mixed|string $implementation
     */
    public function addContextualBinding(string $concrete, string $dependency, Closure|string $implementation): void
    {
        $this->contextual[$concrete][$dependency] = $implementation;
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|string $abstract
     * @param array<string, mixed> $parameters
     *
     * @return ($abstract is class-string<T> ? T : mixed)
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        $instanceOwner = $this->resolveInstanceOwner($abstract);
        if ($instanceOwner !== null && $parameters === []) {
            return $instanceOwner->instances[$abstract];
        }

        $bindingOwner = $this->resolveBindingOwner($abstract);
        if ($bindingOwner !== null) {
            $concrete = $bindingOwner->bindings[$abstract]['concrete'];
            $shared = $bindingOwner->bindings[$abstract]['shared'];
            $storeIn = $bindingOwner;
        } else {
            $concrete = $abstract;
            $shared = $this->isMarkedSingleton($abstract);
            $storeIn = $this->root();
        }

        if ($concrete instanceof Closure) {
            $object = $concrete($this, $parameters);
        } elseif ($concrete === $abstract) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        if ($shared && $parameters === []) {
            $storeIn->instances[$abstract] = $object;
        }

        return $object;
    }

    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw NotFoundException::forId($id);
        }

        return $this->make($id);
    }

    public function has(string $id): bool
    {
        $id = $this->getAlias($id);

        if ($this->resolveInstanceOwner($id) !== null) {
            return true;
        }

        if ($this->resolveBindingOwner($id) !== null) {
            return true;
        }

        return class_exists($id) && (new ReflectionClass($id))->isInstantiable();
    }

    /**
     * The Closure may declare any parameter types; the container resolves them by type.
     *
     * @param Closure(mixed...): mixed|array{0: object|class-string, 1: non-empty-string}|string $callback
     * @param array<string, mixed> $parameters
     */
    public function call(Closure|array|string $callback, array $parameters = []): mixed
    {
        if (is_string($callback)) {
            $callback = $this->normalizeStringCallback($callback);
        }

        if ($callback instanceof Closure) {
            $reflector = new ReflectionFunction($callback);
            $args = $this->resolveDependencies($reflector->getParameters(), $parameters, $reflector->getName());

            return $callback(...$args);
        }

        /** @var array{0: object|class-string, 1: non-empty-string} $callback */
        $target = $callback[0];
        $method = $callback[1];
        $object = is_string($target) ? $this->resolveCallableTarget($target, $method) : $target;

        try {
            $reflector = new ReflectionMethod($object, $method);
        } catch (ReflectionException $e) {
            throw new ContainerException($e->getMessage(), previous: $e);
        }

        $args = $this->resolveDependencies($reflector->getParameters(), $parameters, $object::class);

        return $reflector->invokeArgs($reflector->isStatic() ? null : $object, $args);
    }

    public function scoped(): ContainerInterface
    {
        return new self($this);
    }

    /**
     * @param class-string $target
     */
    private function resolveCallableTarget(string $target, string $method): object
    {
        $resolved = $this->make($target);

        if (!is_object($resolved)) {
            throw new ContainerException(
                sprintf('Target [%s] did not resolve to an object to call method [%s] on.', $target, $method),
            );
        }

        return $resolved;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function build(string $concrete, array $parameters): object
    {
        if (!class_exists($concrete)) {
            throw BindingResolutionException::notFound($concrete);
        }

        $reflector = new ReflectionClass($concrete);
        if (!$reflector->isInstantiable()) {
            throw BindingResolutionException::notInstantiable($concrete);
        }

        if (isset($this->buildStack[$concrete])) {
            throw CircularDependencyException::detected($concrete, array_keys($this->buildStack));
        }

        $this->buildStack[$concrete] = true;

        try {
            $constructor = $reflector->getConstructor();
            if ($constructor === null) {
                return $reflector->newInstance();
            }

            $args = $this->resolveDependencies($constructor->getParameters(), $parameters, $concrete);

            return $reflector->newInstanceArgs($args);
        } finally {
            unset($this->buildStack[$concrete]);
        }
    }

    /**
     * @param array<int, ReflectionParameter> $dependencies
     * @param array<string, mixed> $primitives
     *
     * @return list<mixed>
     */
    private function resolveDependencies(array $dependencies, array $primitives, string $consumer): array
    {
        $results = [];

        foreach ($dependencies as $parameter) {
            $name = $parameter->getName();

            if ($parameter->isVariadic()) {
                if (array_key_exists($name, $primitives)) {
                    $value = $primitives[$name];
                    foreach (is_array($value) ? $value : [$value] as $item) {
                        $results[] = $item;
                    }
                }

                continue;
            }

            if (array_key_exists($name, $primitives)) {
                $results[] = $primitives[$name];

                continue;
            }

            $injectId = $this->injectionId($parameter);
            if ($injectId !== null) {
                $results[] = $this->make($injectId);

                continue;
            }

            $className = $this->dependencyClassName($parameter->getType());
            if ($className !== null) {
                $results[] = $this->resolveClassDependency($parameter, $className, $consumer);

                continue;
            }

            $results[] = $this->resolvePrimitive($parameter, $consumer);
        }

        return $results;
    }

    private function resolveClassDependency(ReflectionParameter $parameter, string $className, string $consumer): mixed
    {
        $contextual = $this->findContextual($consumer, $className);
        if ($contextual !== null) {
            return $contextual instanceof Closure ? $contextual($this) : $this->make($contextual);
        }

        try {
            return $this->make($className);
        } catch (BindingResolutionException $e) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            if ($parameter->allowsNull()) {
                return null;
            }

            throw $e;
        }
    }

    private function resolvePrimitive(ReflectionParameter $parameter, string $consumer): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw BindingResolutionException::unresolvablePrimitive($parameter, $consumer);
    }

    private function injectionId(ReflectionParameter $parameter): ?string
    {
        $attributes = $parameter->getAttributes(Inject::class);

        return $attributes === [] ? null : $attributes[0]->newInstance()->id;
    }

    private function dependencyClassName(?ReflectionType $type): ?string
    {
        if ($type instanceof ReflectionNamedType) {
            return $this->namedClass($type);
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $inner) {
                if ($inner instanceof ReflectionNamedType) {
                    $class = $this->namedClass($inner);
                    if ($class !== null) {
                        return $class;
                    }
                }
            }
        }

        return null;
    }

    private function namedClass(ReflectionNamedType $type): ?string
    {
        if ($type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        return in_array($name, ['self', 'static', 'parent'], true) ? null : $name;
    }

    private function isMarkedSingleton(string $abstract): bool
    {
        if (isset($this->singletonAttributeCache[$abstract])) {
            return $this->singletonAttributeCache[$abstract];
        }

        $marked = class_exists($abstract)
            && (new ReflectionClass($abstract))->getAttributes(Singleton::class) !== [];

        return $this->singletonAttributeCache[$abstract] = $marked;
    }

    /**
     * @return Closure(mixed...): mixed|array{0: class-string, 1: string}
     */
    private function normalizeStringCallback(string $callback): Closure|array
    {
        foreach (['@', '::'] as $separator) {
            if (str_contains($callback, $separator)) {
                [$class, $method] = explode($separator, $callback, 2);
                /** @var class-string $class */
                return [$class, $method];
            }
        }

        if (function_exists($callback)) {
            return Closure::fromCallable($callback);
        }

        throw new ContainerException(sprintf('Invalid callable string [%s].', $callback));
    }

    private function getAlias(string $abstract): string
    {
        /** @var array<string, true> $seen */
        $seen = [];

        while (($target = $this->lookupAlias($abstract)) !== null) {
            if (isset($seen[$abstract])) {
                break;
            }
            $seen[$abstract] = true;
            $abstract = $target;
        }

        return $abstract;
    }

    private function lookupAlias(string $abstract): ?string
    {
        $container = $this;
        while ($container !== null) {
            if (isset($container->aliases[$abstract])) {
                return $container->aliases[$abstract];
            }
            $container = $container->parent;
        }

        return null;
    }

    private function resolveInstanceOwner(string $abstract): ?Container
    {
        $container = $this;
        while ($container !== null) {
            if (array_key_exists($abstract, $container->instances)) {
                return $container;
            }
            $container = $container->parent;
        }

        return null;
    }

    private function resolveBindingOwner(string $abstract): ?Container
    {
        $container = $this;
        while ($container !== null) {
            if (isset($container->bindings[$abstract])) {
                return $container;
            }
            $container = $container->parent;
        }

        return null;
    }

    /**
     * @return Closure(ContainerInterface): mixed|string|null
     */
    private function findContextual(string $consumer, string $dependency): Closure|string|null
    {
        $container = $this;
        while ($container !== null) {
            if (isset($container->contextual[$consumer][$dependency])) {
                return $container->contextual[$consumer][$dependency];
            }
            $container = $container->parent;
        }

        return null;
    }

    private function root(): Container
    {
        $container = $this;
        while ($container->parent !== null) {
            $container = $container->parent;
        }

        return $container;
    }
}

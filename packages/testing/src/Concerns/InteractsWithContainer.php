<?php

declare(strict_types=1);

namespace Docile\Testing\Concerns;

use Docile\Foundation\Application;
use Psr\Container\ContainerInterface;

trait InteractsWithContainer
{
    abstract protected function app(): Application;

    /** @var array<string, mixed> */
    private array $originalBindings = [];

    /**
     * @param \Closure(ContainerInterface, array<string, mixed>): mixed|string|null $concrete
     */
    protected function bind(string $abstract, $concrete = null): void
    {
        $container = $this->app()->container();

        if ($container->has($abstract)) {
            try {
                $original = $container->get($abstract);
                if (is_object($original)) {
                    $this->originalBindings[$abstract] = $original;
                }
            } catch (\Throwable) {
            }
        }

        $container->bind($abstract, $concrete);
    }

    protected function instance(string $abstract, object $concrete): void
    {
        $container = $this->app()->container();

        if ($container->has($abstract)) {
            $this->originalBindings[$abstract] = $container->get($abstract);
        }

        $container->instance($abstract, $concrete);
    }

    protected function tearDownInteractsWithContainer(): void
    {
        foreach ($this->originalBindings as $abstract => $concrete) {
            $container = $this->app()->container();
            $container->instance($abstract, $concrete);
        }

        $this->originalBindings = [];
    }
}

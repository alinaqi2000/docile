<?php

declare(strict_types=1);

namespace Docile\Foundation\Bootstrap;

use Docile\Foundation\Application;
use Docile\Foundation\BootstrapperInterface;
use Docile\Foundation\ServiceProviderInterface;

/**
 * Instantiates each service provider, calls register() on all of them first,
 * then calls boot() on all of them in the same order.
 */
final class RegisterServiceProviders implements BootstrapperInterface
{
    /**
     * @param array<class-string<ServiceProviderInterface>> $providers
     */
    public function __construct(private readonly array $providers) {}

    public function bootstrap(Application $app): void
    {
        $container = $app->container();

        /** @var list<ServiceProviderInterface> $instances */
        $instances = [];

        foreach ($this->providers as $providerClass) {
            $provider = new $providerClass();
            $provider->register($container);
            $instances[] = $provider;
        }

        foreach ($instances as $provider) {
            $provider->boot($container);
        }
    }
}

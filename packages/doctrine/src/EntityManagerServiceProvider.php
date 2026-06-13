<?php

declare(strict_types=1);

namespace Docile\Doctrine;

use Docile\Foundation\AbstractServiceProvider;
use Docile\Container\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;

final class EntityManagerServiceProvider extends AbstractServiceProvider
{
    #[\Override]
    public function register(ContainerInterface $container): void
    {
        $container->singleton(EntityManagerInterface::class, function (ContainerInterface $container) {
            $config = $container->make('config');
            
            if (!is_object($config) || !method_exists($config, 'get')) {
                throw new \RuntimeException('Config service must implement get() method.');
            }

            $dbConfig = $config->get('database');
            
            if (!is_array($dbConfig)) {
                throw new \RuntimeException('Database configuration must be an array.');
            }

            /** @var array<string,mixed> $dbConfig */
            $entityPath = $dbConfig['entity_path'] ?? null;
            $proxyDir = $dbConfig['proxy_dir'] ?? null;

            if ($entityPath === null || $proxyDir === null) {
                throw new \RuntimeException('Database configuration must include "entity_path" and "proxy_dir".');
            }

            if (!is_string($entityPath) || !is_string($proxyDir)) {
                throw new \RuntimeException('entity_path and proxy_dir must be strings.');
            }

            return EntityManagerFactory::create($dbConfig, $entityPath, $proxyDir);
        });
    }
}

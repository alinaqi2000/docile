<?php

declare(strict_types=1);

namespace Docile\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Docile\Doctrine\Exception\DoctrineException;

final class EntityManagerFactory
{
    /**
     * @param array<string,mixed> $config
     * @param string $entityPath
     * @param string $proxyDir
     * @return EntityManagerInterface
     */
    public static function create(array $config, string $entityPath, string $proxyDir): EntityManagerInterface
    {
        $driver = $config['driver'] ?? 'pdo_mysql';
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? null;
        $dbname = $config['dbname'] ?? null;
        $user = $config['user'] ?? null;
        $password = $config['password'] ?? null;
        $charset = $config['charset'] ?? 'utf8mb4';

        $dbalConfig = [
            'driver' => $driver,
            'host' => $host,
            'charset' => $charset,
        ];

        if ($port !== null) {
            $dbalConfig['port'] = $port;
        }

        if ($dbname !== null) {
            $dbalConfig['dbname'] = $dbname;
        }

        if ($user !== null) {
            $dbalConfig['user'] = $user;
        }

        if ($password !== null) {
            $dbalConfig['password'] = $password;
        }

        /** @var array<string,mixed> $dbalConfig */
        $ormConfig = new Configuration();
        $ormConfig->setMetadataDriverImpl(new AttributeDriver([$entityPath]));
        $ormConfig->setProxyDir($proxyDir);
        $ormConfig->setProxyNamespace('DoctrineProxies');
        $ormConfig->setAutoGenerateProxyClasses(false);

        try {
            // @phpstan-ignore argument.type
            $connection = DriverManager::getConnection($dbalConfig, $ormConfig);
        } catch (\Throwable $e) {
            throw new DoctrineException(sprintf('Failed to create database connection: %s', $e->getMessage()), 0, $e);
        }

        try {
            return new EntityManager($connection, $ormConfig);
        } catch (\Throwable $e) {
            throw new DoctrineException(sprintf('Failed to create entity manager: %s', $e->getMessage()), 0, $e);
        }
    }
}

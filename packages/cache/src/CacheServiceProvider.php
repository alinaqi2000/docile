<?php

declare(strict_types=1);

namespace Docile\Cache;

use Docile\Cache\Backend\ApcuCache;
use Docile\Cache\Backend\ArrayCache;
use Docile\Cache\Backend\FilesystemCache;
use Docile\Cache\Backend\NullCache;
use Docile\Cache\Backend\RedisCache;
use Docile\Config\Repository;
use Docile\Container\ContainerInterface;
use Docile\Foundation\AbstractServiceProvider;
use Redis;

use function sys_get_temp_dir;

/**
 * Cache service provider.
 */
final class CacheServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(CacheInterface::class, static function (ContainerInterface $container) {
            $config = $container->get(Repository::class);

            if (!$config instanceof Repository) {
                return new ArrayCache();
            }

            $driver = $config->string('cache.default', 'array');

            return match ($driver) {
                'array' => new ArrayCache(),
                'file' => new FilesystemCache($config->string('cache.file.path', sys_get_temp_dir() . '/cache')),
                'apcu' => new ApcuCache($config->string('cache.apcu.prefix', '')),
                'redis' => self::createRedisCache($container, $config),
                'null' => new NullCache(),
                default => new ArrayCache(),
            };
        });
    }

    private static function createRedisCache(ContainerInterface $container, Repository $config): RedisCache
    {
        $redis = $container->has(Redis::class) ? $container->get(Redis::class) : null;

        if ($redis instanceof Redis) {
            return new RedisCache($redis, $config->string('cache.redis.prefix', ''));
        }

        $redis = new Redis();
        $redis->connect(
            $config->string('cache.redis.host', '127.0.0.1'),
            $config->int('cache.redis.port', 6379),
        );

        $password = $config->string('cache.redis.password', '');

        if ($password !== '') {
            $redis->auth($password);
        }

        $database = $config->int('cache.redis.database', 0);

        if ($database > 0) {
            $redis->select($database);
        }

        return new RedisCache($redis, $config->string('cache.redis.prefix', ''));
    }
}

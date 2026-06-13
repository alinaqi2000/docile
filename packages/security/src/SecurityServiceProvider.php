<?php

declare(strict_types=1);

namespace Docile\Security;

use Docile\Foundation\AbstractServiceProvider;
use Docile\Container\ContainerInterface;
use Docile\Security\Auth\Token\JwtCodec;
use Docile\Security\Auth\Token\TokenGuard;
use Docile\Security\Authz\Gate;
use Docile\Security\Authz\GateInterface;
use Docile\Security\Csrf\CsrfMiddleware;
use Docile\Security\Csrf\CsrfTokenManager;
use Docile\Security\Password\BcryptHasher;
use Docile\Security\Password\HasherInterface;
use Docile\Security\Password\SodiumHasher;
use Docile\Security\RateLimit\RateLimitMiddleware;
use Docile\Security\RateLimit\RateLimiterInterface;
use Docile\Security\RateLimit\TokenBucketLimiter;
use Docile\Support\Clock\SystemClock;
use Psr\SimpleCache\CacheInterface;

final class SecurityServiceProvider extends AbstractServiceProvider
{
    #[\Override]
    public function register(ContainerInterface $container): void
    {
        $container->singleton(HasherInterface::class, SodiumHasher::class);

        $container->singleton(GateInterface::class, Gate::class);

        $container->singleton(JwtCodec::class, JwtCodec::class);

        $container->singleton(TokenGuard::class, function (ContainerInterface $container) {
            $codec = $container->make(JwtCodec::class);

            if (!$codec instanceof JwtCodec) {
                throw new \RuntimeException('Expected JwtCodec instance.');
            }

            return new TokenGuard($codec);
        });

        $container->singleton(CsrfTokenManager::class, CsrfTokenManager::class);

        $container->singleton(CsrfMiddleware::class, function (ContainerInterface $container) {
            $manager = $container->make(CsrfTokenManager::class);

            if (!$manager instanceof CsrfTokenManager) {
                throw new \RuntimeException('Expected CsrfTokenManager instance.');
            }

            return new CsrfMiddleware($manager);
        });

        $container->singleton(RateLimiterInterface::class, function (ContainerInterface $container) {
            $cache = $container->has(CacheInterface::class)
                ? $container->make(CacheInterface::class)
                : $this->createArrayCache();

            if (!$cache instanceof CacheInterface) {
                throw new \RuntimeException('Expected CacheInterface instance.');
            }

            return new TokenBucketLimiter($cache, new SystemClock());
        });

        $container->singleton(RateLimitMiddleware::class, function (ContainerInterface $container) {
            $limiter = $container->make(RateLimiterInterface::class);

            if (!$limiter instanceof RateLimiterInterface) {
                throw new \RuntimeException('Expected RateLimiterInterface instance.');
            }

            return new RateLimitMiddleware($limiter);
        });
    }

    private function createArrayCache(): CacheInterface
    {
        return new class implements CacheInterface {
            /** @var array<string, mixed> */
            private array $storage = [];

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->storage[$key] ?? $default;
            }

            public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
            {
                $this->storage[$key] = $value;

                return true;
            }

            public function delete(string $key): bool
            {
                unset($this->storage[$key]);

                return true;
            }

            public function clear(): bool
            {
                $this->storage = [];

                return true;
            }

            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                $result = [];

                foreach ($keys as $key) {
                    $result[$key] = $this->storage[$key] ?? $default;
                }

                return $result;
            }

            public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool // @phpstan-ignore-line
            {
                foreach ($values as $key => $value) {
                    if (is_string($key)) {
                        $this->storage[$key] = $value;
                    }
                }

                return true;
            }

            public function deleteMultiple(iterable $keys): bool
            {
                foreach ($keys as $key) {
                    unset($this->storage[$key]);
                }

                return true;
            }

            public function has(string $key): bool
            {
                return isset($this->storage[$key]);
            }
        };
    }
}

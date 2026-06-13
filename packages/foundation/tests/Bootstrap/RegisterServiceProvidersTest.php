<?php

declare(strict_types=1);

namespace Docile\Foundation\Tests\Bootstrap;

use Docile\Container\Container;
use Docile\Container\ContainerInterface;
use Docile\Foundation\AbstractServiceProvider;
use Docile\Foundation\Application;
use Docile\Foundation\Bootstrap\RegisterServiceProviders;
use Docile\Foundation\Tests\Fixtures\TestServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RegisterServiceProviders::class)]
#[CoversClass(AbstractServiceProvider::class)]
final class RegisterServiceProvidersTest extends TestCase
{
    public function testBootstrapCallsRegisterOnEachProvider(): void
    {
        $container = new Container();
        $app = new Application($container);

        $bootstrapper = new RegisterServiceProviders([TestServiceProvider::class]);
        $bootstrapper->bootstrap($app);

        // TestServiceProvider binds 'test' => fn() => 'value'
        self::assertSame('value', $container->get('test'));
    }

    public function testBootstrapCallsRegisterThenBootOnAllProviders(): void
    {
        // Use static state on named classes to track call order
        OrderTrackingProviderA::reset(); // also resets B's entries since they share the same log

        $container = new Container();
        $app = new Application($container);

        $bootstrapper = new RegisterServiceProviders([
            OrderTrackingProviderA::class,
            OrderTrackingProviderB::class,
        ]);
        $bootstrapper->bootstrap($app);

        // All registers come before any boots
        self::assertSame(
            ['A:register', 'B:register', 'A:boot', 'B:boot'],
            OrderTrackingProviderA::$log,
        );
    }

    public function testAbstractServiceProviderBootIsNoopByDefault(): void
    {
        $provider = new NoBootProvider();
        $container = new Container();

        $provider->register($container);
        $provider->boot($container); // no-op — must not throw

        self::assertTrue($provider->registerCalled);
    }

    public function testBootstrapWithNoProvidersDoesNothing(): void
    {
        $container = new Container();
        $app = new Application($container);

        $bootstrapper = new RegisterServiceProviders([]);
        $bootstrapper->bootstrap($app); // Must not throw

        $this->addToAssertionCount(1);
    }

    public function testBootstrapPassesContainerToProviders(): void
    {
        ContainerCapturingProvider::$captured = null;

        $container = new Container();
        $app = new Application($container);

        $bootstrapper = new RegisterServiceProviders([ContainerCapturingProvider::class]);
        $bootstrapper->bootstrap($app);

        self::assertSame($container, ContainerCapturingProvider::$captured);
    }
}

// ---------------------------------------------------------------------------
// Named helper classes (must be no-arg constructable for RegisterServiceProviders)
// ---------------------------------------------------------------------------

/**
 * Logs "A:register" / "A:boot" to a shared static log.
 */
final class OrderTrackingProviderA extends AbstractServiceProvider
{
    /** @var list<string> */
    public static array $log = [];

    public static function reset(): void
    {
        self::$log = [];
    }

    public function register(ContainerInterface $container): void
    {
        self::$log[] = 'A:register';
    }

    public function boot(ContainerInterface $container): void
    {
        self::$log[] = 'A:boot';
    }
}

/**
 * Logs "B:register" / "B:boot" to the same static log as A.
 */
final class OrderTrackingProviderB extends AbstractServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        OrderTrackingProviderA::$log[] = 'B:register';
    }

    public function boot(ContainerInterface $container): void
    {
        OrderTrackingProviderA::$log[] = 'B:boot';
    }
}

/**
 * Provider with only register() — boot() should be the parent no-op.
 */
final class NoBootProvider extends AbstractServiceProvider
{
    public bool $registerCalled = false;

    public function register(ContainerInterface $container): void
    {
        $this->registerCalled = true;
    }
}

/**
 * Provider that captures the container it is given during register().
 */
final class ContainerCapturingProvider extends AbstractServiceProvider
{
    public static ?ContainerInterface $captured = null;

    public function register(ContainerInterface $container): void
    {
        self::$captured = $container;
    }
}

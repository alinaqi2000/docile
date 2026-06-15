<?php

declare(strict_types=1);

namespace Docile\Container\Tests;

use Docile\Container\Container;
use Docile\Container\ContainerInterface;
use Docile\Container\Exception\BindingResolutionException;
use Docile\Container\Exception\CircularDependencyException;
use Docile\Container\Exception\ContainerException;
use Docile\Container\Exception\NotFoundException;
use Docile\Container\Tests\Fixtures\AbstractBase;
use Docile\Container\Tests\Fixtures\CallTarget;
use Docile\Container\Tests\Fixtures\CircularA;
use Docile\Container\Tests\Fixtures\ContextualConsumer;
use Docile\Container\Tests\Fixtures\FileLogger;
use Docile\Container\Tests\Fixtures\HasPrimitiveDefault;
use Docile\Container\Tests\Fixtures\InjectExample;
use Docile\Container\Tests\Fixtures\Invokable;
use Docile\Container\Tests\Fixtures\Logger;
use Docile\Container\Tests\Fixtures\LoggerInterface;
use Docile\Container\Tests\Fixtures\NullableInterfaceDependency;
use Docile\Container\Tests\Fixtures\NullLogger;
use Docile\Container\Tests\Fixtures\RequiresPrimitive;
use Docile\Container\Tests\Fixtures\ServiceWithDependency;
use Docile\Container\Tests\Fixtures\SingletonMarked;
use Docile\Container\Tests\Fixtures\VariadicConsumer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

use function is_string;

#[CoversClass(Container::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularDependencyException::class)]
#[CoversClass(NotFoundException::class)]
final class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    // ---------------------------------------------------------------- autowiring & binding

    public function testMakeAutowiresConcreteClassWithNoDependencies(): void
    {
        self::assertInstanceOf(Logger::class, $this->container->make(Logger::class));
    }

    public function testMakeAutowiresNestedDependencies(): void
    {
        /** @var ServiceWithDependency $service */
        $service = $this->container->make(ServiceWithDependency::class);

        self::assertInstanceOf(ServiceWithDependency::class, $service);
        // $service->logger is already typed Logger in ServiceWithDependency; verify it was autowired
        self::assertInstanceOf(Logger::class, $service->logger); // @phpstan-ignore staticMethod.alreadyNarrowedType
    }

    public function testBindResolvesNewTransientInstanceEachTime(): void
    {
        $this->container->bind(Logger::class);

        self::assertNotSame(
            $this->container->make(Logger::class),
            $this->container->make(Logger::class),
        );
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $this->container->singleton(Logger::class);

        self::assertSame(
            $this->container->make(Logger::class),
            $this->container->make(Logger::class),
        );
    }

    public function testSingletonClosureIsResolvedOnce(): void
    {
        $this->container->singleton('config', static fn(): stdClass => new stdClass());

        self::assertSame($this->container->make('config'), $this->container->make('config'));
    }

    public function testInstanceReturnsRegisteredInstance(): void
    {
        $logger = new Logger();

        self::assertSame($logger, $this->container->instance(Logger::class, $logger));
        self::assertSame($logger, $this->container->make(Logger::class));
    }

    public function testBindInterfaceToImplementation(): void
    {
        $this->container->bind(LoggerInterface::class, FileLogger::class);

        self::assertInstanceOf(FileLogger::class, $this->container->make(LoggerInterface::class));
    }

    public function testBindClosureReceivesContainer(): void
    {
        $this->container->bind('self', static fn(ContainerInterface $c): ContainerInterface => $c);

        self::assertSame($this->container, $this->container->make('self'));
    }

    public function testBindClosureReceivesParameters(): void
    {
        $this->container->bind(
            'greeting',
            static function (ContainerInterface $c, array $params): string {
                $name = isset($params['name']) && is_string($params['name']) ? $params['name'] : 'world';

                return 'hello ' . $name;
            },
        );

        self::assertSame('hello devin', $this->container->make('greeting', ['name' => 'devin']));
    }

    public function testMakeWithParameterOverrides(): void
    {
        /** @var RequiresPrimitive $object */
        $object = $this->container->make(RequiresPrimitive::class, ['name' => 'custom']);

        self::assertSame('custom', $object->name);
    }

    public function testMakeWithParametersBypassesInstanceCache(): void
    {
        $shared = new HasPrimitiveDefault('shared');
        $this->container->instance(HasPrimitiveDefault::class, $shared);

        /** @var HasPrimitiveDefault $fresh */
        $fresh = $this->container->make(HasPrimitiveDefault::class, ['name' => 'fresh']);

        self::assertNotSame($shared, $fresh);
        self::assertSame('fresh', $fresh->name);
        self::assertSame($shared, $this->container->make(HasPrimitiveDefault::class));
    }

    public function testRebindingClearsCachedInstance(): void
    {
        $this->container->singleton(Logger::class);
        $first = $this->container->make(Logger::class);

        $this->container->bind(Logger::class);

        self::assertNotSame($first, $this->container->make(Logger::class));
    }

    // ---------------------------------------------------------------- PSR-11 get/has

    public function testGetResolvesEntry(): void
    {
        self::assertInstanceOf(Logger::class, $this->container->get(Logger::class));
    }

    public function testGetThrowsNotFoundForUnknownIdentifier(): void
    {
        $this->expectException(NotFoundException::class);

        $this->container->get('some.unbound.service');
    }

    public function testHasReturnsTrueForBinding(): void
    {
        $this->container->bind(LoggerInterface::class, FileLogger::class);

        self::assertTrue($this->container->has(LoggerInterface::class));
    }

    public function testHasReturnsTrueForInstantiableClass(): void
    {
        self::assertTrue($this->container->has(Logger::class));
    }

    public function testHasReturnsFalseForUnboundInterface(): void
    {
        self::assertFalse($this->container->has(LoggerInterface::class));
    }

    public function testHasReturnsFalseForUnknownString(): void
    {
        self::assertFalse($this->container->has('totally.unknown'));
    }

    // ---------------------------------------------------------------- aliases

    public function testAliasResolvesToAbstract(): void
    {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $this->container->alias(LoggerInterface::class, 'log');

        self::assertInstanceOf(FileLogger::class, $this->container->make('log'));
    }

    public function testAliasChainResolves(): void
    {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $this->container->alias(LoggerInterface::class, 'log');
        $this->container->alias('log', 'logger');

        self::assertInstanceOf(FileLogger::class, $this->container->make('logger'));
    }

    public function testAliasToSelfThrows(): void
    {
        $this->expectException(ContainerException::class);

        $this->container->alias('x', 'x');
    }

    // ---------------------------------------------------------------- attributes

    public function testSingletonAttributeSharesInstance(): void
    {
        self::assertSame(
            $this->container->make(SingletonMarked::class),
            $this->container->make(SingletonMarked::class),
        );
    }

    public function testInjectAttributeResolvesById(): void
    {
        $this->container->bind('app.name', static fn(): string => 'Docile');

        /** @var InjectExample $result */
        $result = $this->container->make(InjectExample::class);
        self::assertSame('Docile', $result->appName);
    }

    // ---------------------------------------------------------------- contextual bindings

    public function testContextualBindingWithClassName(): void
    {
        $this->container->when(ContextualConsumer::class)
            ->needs(LoggerInterface::class)
            ->give(FileLogger::class);

        /** @var ContextualConsumer $consumer */
        $consumer = $this->container->make(ContextualConsumer::class);
        self::assertInstanceOf(FileLogger::class, $consumer->logger);
    }

    public function testContextualBindingWithClosure(): void
    {
        $this->container->when(ContextualConsumer::class)
            ->needs(LoggerInterface::class)
            ->give(static fn(): LoggerInterface => new NullLogger());

        /** @var ContextualConsumer $consumer */
        $consumer = $this->container->make(ContextualConsumer::class);
        self::assertInstanceOf(NullLogger::class, $consumer->logger);
    }

    // ---------------------------------------------------------------- resolution errors

    public function testUnresolvablePrimitiveThrows(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->container->make(RequiresPrimitive::class);
    }

    public function testPrimitiveDefaultIsUsed(): void
    {
        /** @var HasPrimitiveDefault $object */
        $object = $this->container->make(HasPrimitiveDefault::class);

        self::assertSame('default', $object->name);
        self::assertSame(3, $object->count);
    }

    public function testNullableUnresolvableDependencyResolvesToNull(): void
    {
        /** @var NullableInterfaceDependency $dep */
        $dep = $this->container->make(NullableInterfaceDependency::class);
        self::assertNull($dep->logger);
    }

    public function testAbstractClassThrowsNotInstantiable(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('not instantiable');

        $this->container->make(AbstractBase::class);
    }

    public function testUnboundInterfaceThrowsBindingResolution(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->container->make(LoggerInterface::class);
    }

    public function testCircularDependencyThrows(): void
    {
        $this->expectException(CircularDependencyException::class);
        $this->expectExceptionMessage('Circular dependency');

        $this->container->make(CircularA::class);
    }

    // ---------------------------------------------------------------- call()

    public function testCallInvokesClosureWithInjection(): void
    {
        // @phpstan-ignore argument.type (Closure param type is narrower than mixed, intentional for DI test)
        $result = $this->container->call(static fn(Logger $logger): string => $logger::class);

        self::assertSame(Logger::class, $result);
    }

    public function testCallInvokesInstanceMethodArray(): void
    {
        $result = $this->container->call([new CallTarget(), 'handle'], ['suffix' => 'done']);

        self::assertSame(Logger::class . ':done', $result);
    }

    public function testCallInvokesClassStringMethodArray(): void
    {
        $result = $this->container->call([CallTarget::class, 'handle'], ['suffix' => 'x']);

        self::assertSame(Logger::class . ':x', $result);
    }

    public function testCallInvokesAtString(): void
    {
        $result = $this->container->call(CallTarget::class . '@handle', ['suffix' => 'y']);

        self::assertSame(Logger::class . ':y', $result);
    }

    public function testCallInvokesStaticMethodString(): void
    {
        $result = $this->container->call(CallTarget::class . '::staticHandle');

        self::assertSame('static:' . Logger::class, $result);
    }

    public function testCallInvokesInvokableObject(): void
    {
        $result = $this->container->call([new Invokable(), '__invoke'], ['suffix' => '?']);

        self::assertSame(Logger::class . '?', $result);
    }

    public function testCallInvokesFunctionString(): void
    {
        self::assertSame('HI', $this->container->call('strtoupper', ['string' => 'hi']));
    }

    public function testCallWithInvalidStringThrows(): void
    {
        $this->expectException(ContainerException::class);

        $this->container->call('not_a_real_function');
    }

    public function testCallThrowsForMissingMethod(): void
    {
        $this->expectException(ContainerException::class);

        $this->container->call([CallTarget::class, 'doesNotExist']);
    }

    public function testCallThrowsWhenTargetResolvesToNonObject(): void
    {
        $this->container->bind('scalar', static fn(): string => 'not-an-object');

        $this->expectException(ContainerException::class);

        /** @var class-string $fakeClass @phpstan-ignore varTag.nativeType */
        $fakeClass = 'scalar';
        $this->container->call([$fakeClass, 'method']);
    }

    // ---------------------------------------------------------------- variadics

    public function testVariadicResolvesToEmptyWhenNotProvided(): void
    {
        /** @var VariadicConsumer $consumer */
        $consumer = $this->container->make(VariadicConsumer::class);
        self::assertSame([], $consumer->numbers);
    }

    public function testVariadicAcceptsArrayOverride(): void
    {
        /** @var VariadicConsumer $object */
        $object = $this->container->make(VariadicConsumer::class, ['numbers' => [1, 2, 3]]);

        self::assertSame([1, 2, 3], $object->numbers);
    }

    // ---------------------------------------------------------------- scopes

    public function testScopedInheritsRootSingleton(): void
    {
        $this->container->singleton(Logger::class);
        $scope = $this->container->scoped();

        self::assertSame($this->container->make(Logger::class), $scope->make(Logger::class));
    }

    public function testScopedSingletonResolvedFirstInScopeIsStoredAtRoot(): void
    {
        $this->container->singleton(Logger::class);
        $scope = $this->container->scoped();

        $fromScope = $scope->make(Logger::class);

        self::assertSame($fromScope, $this->container->make(Logger::class));
    }

    public function testScopedInstanceOverrideDoesNotLeakToRoot(): void
    {
        $rootLogger = new Logger();
        $this->container->instance(Logger::class, $rootLogger);

        $scope = $this->container->scoped();
        $scopeLogger = new Logger();
        $scope->instance(Logger::class, $scopeLogger);

        self::assertSame($scopeLogger, $scope->make(Logger::class));
        self::assertSame($rootLogger, $this->container->make(Logger::class));
    }

    public function testScopedResolvesAutowiredClasses(): void
    {
        $scope = $this->container->scoped();

        self::assertInstanceOf(ServiceWithDependency::class, $scope->make(ServiceWithDependency::class));
    }

    public function testScopedInheritsBindings(): void
    {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $scope = $this->container->scoped();

        self::assertInstanceOf(FileLogger::class, $scope->make(LoggerInterface::class));
    }
}

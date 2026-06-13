<?php

declare(strict_types=1);

namespace Docile\Events\Tests;

use Docile\Events\ListenerProvider;
use Docile\Events\Tests\Fixtures\OrderPlaced;
use Docile\Events\Tests\Fixtures\PrioritySubscriber;
use Docile\Events\Tests\Fixtures\UserRegistered;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ListenerProvider::class)]
final class ListenerProviderTest extends TestCase
{
    private ListenerProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ListenerProvider();
    }

    public function testNoListenersReturnsEmptyIterable(): void
    {
        $event = new OrderPlaced(1);
        $listeners = [...$this->provider->getListenersForEvent($event)];

        self::assertSame([], $listeners);
    }

    public function testAddedListenerIsReturnedForMatchingEvent(): void
    {
        $called = false;
        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$called): void {
            $called = true;
        });

        $event = new OrderPlaced(1);
        $listeners = [...$this->provider->getListenersForEvent($event)];

        self::assertCount(1, $listeners);
    }

    public function testListenersAreReturnedInPriorityDescendingOrder(): void
    {
        $log = [];

        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$log): void {
            $log[] = 'low';
        }, 0);

        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$log): void {
            $log[] = 'high';
        }, 10);

        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$log): void {
            $log[] = 'medium';
        }, 5);

        $event = new OrderPlaced(42);
        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['high', 'medium', 'low'], $log);
    }

    public function testListenersAtSamePriorityAreReturnedInInsertionOrder(): void
    {
        $log = [];

        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$log): void {
            $log[] = 'first';
        }, 5);

        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$log): void {
            $log[] = 'second';
        }, 5);

        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$log): void {
            $log[] = 'third';
        }, 5);

        $event = new OrderPlaced(1);
        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['first', 'second', 'third'], $log);
    }

    public function testListenerForDifferentEventClassIsNotReturned(): void
    {
        $this->provider->addListener(OrderPlaced::class, static function (object $event): void {});

        $event = new UserRegistered('test@example.com');
        $listeners = [...$this->provider->getListenersForEvent($event)];

        self::assertSame([], $listeners);
    }

    public function testParentClassListenerIsNotReturnedForChildEvent(): void
    {
        // Register listener specifically for UserRegistered (a child of StoppableEvent)
        // but dispatch OrderPlaced — should get no listeners
        $this->provider->addListener(UserRegistered::class, static function (object $event): void {});

        $event = new OrderPlaced(1);
        $listeners = [...$this->provider->getListenersForEvent($event)];

        self::assertSame([], $listeners);
    }

    public function testSubscribeRegistersAttributeAnnotatedMethods(): void
    {
        $subscriber = new PrioritySubscriber();
        $this->provider->subscribe($subscriber);

        $event = new OrderPlaced(7);
        $listeners = [...$this->provider->getListenersForEvent($event)];

        // PrioritySubscriber has 3 #[AsListener] methods for OrderPlaced
        self::assertCount(3, $listeners);
    }

    public function testSubscribeRegistersCorrectPrioritiesViaAttributeDiscovery(): void
    {
        $subscriber = new PrioritySubscriber();
        $this->provider->subscribe($subscriber);

        $event = new OrderPlaced(99);
        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        // high (10) → medium (5) → low (0)
        self::assertSame(['high:99', 'medium:99', 'low:99'], $subscriber->log);
    }

    public function testSubscribeRegistersListenersForMultipleEventTypes(): void
    {
        $subscriber = new PrioritySubscriber();
        $this->provider->subscribe($subscriber);

        $orderListeners = [...$this->provider->getListenersForEvent(new OrderPlaced(1))];
        $userListeners  = [...$this->provider->getListenersForEvent(new UserRegistered('a@b.com'))];

        self::assertCount(3, $orderListeners);
        self::assertCount(1, $userListeners);
    }

    public function testSubscribeUserRegisteredListenerIsCalledCorrectly(): void
    {
        $subscriber = new PrioritySubscriber();
        $this->provider->subscribe($subscriber);

        $event = new UserRegistered('hello@world.com');
        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['user:hello@world.com'], $subscriber->log);
    }

    public function testNegativePriorityListenersOrderedCorrectly(): void
    {
        $log = [];

        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$log): void {
            $log[] = 'minus10';
        }, -10);

        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$log): void {
            $log[] = 'zero';
        }, 0);

        $event = new OrderPlaced(1);
        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        self::assertSame(['zero', 'minus10'], $log);
    }
}

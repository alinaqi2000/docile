<?php

declare(strict_types=1);

namespace Docile\Events\Tests;

use Docile\Events\EventDispatcher;
use Docile\Events\ListenerProvider;
use Docile\Events\Tests\Fixtures\OrderPlaced;
use Docile\Events\Tests\Fixtures\UserRegistered;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventDispatcher::class)]
final class EventDispatcherTest extends TestCase
{
    private ListenerProvider $provider;
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->provider   = new ListenerProvider();
        $this->dispatcher = new EventDispatcher($this->provider);
    }

    public function testDispatchReturnsTheSameEventObject(): void
    {
        $event  = new OrderPlaced(1);
        $result = $this->dispatcher->dispatch($event);

        self::assertSame($event, $result);
    }

    public function testDispatchCallsListeners(): void
    {
        $called = false;
        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$called): void {
            $called = true;
        });

        $this->dispatcher->dispatch(new OrderPlaced(1));

        self::assertTrue($called);
    }

    public function testDispatchCallsListenersInPriorityOrder(): void
    {
        $log = [];

        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$log): void {
            $log[] = 'low';
        }, 0);

        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$log): void {
            $log[] = 'high';
        }, 10);

        $this->dispatcher->dispatch(new OrderPlaced(1));

        self::assertSame(['high', 'low'], $log);
    }

    public function testDispatchStopsWhenPropagationIsStopped(): void
    {
        $log = [];

        $this->provider->addListener(UserRegistered::class, static function (object $event) use (&$log): void {
            $log[] = 'first';
            /** @var UserRegistered $event */
            $event->stopPropagation();
        }, 10);

        $this->provider->addListener(UserRegistered::class, static function (object $event) use (&$log): void {
            $log[] = 'second';
        }, 5);

        $this->provider->addListener(UserRegistered::class, static function (object $event) use (&$log): void {
            $log[] = 'third';
        }, 0);

        $this->dispatcher->dispatch(new UserRegistered('test@example.com'));

        self::assertSame(['first'], $log);
    }

    public function testDispatchDoesNotCallAnyListenerIfAlreadyStopped(): void
    {
        $called = false;
        $this->provider->addListener(UserRegistered::class, static function (object $event) use (&$called): void {
            $called = true;
        });

        $event = new UserRegistered('pre@stopped.com');
        $event->stopPropagation();

        $this->dispatcher->dispatch($event);

        self::assertFalse($called);
    }

    public function testDispatchWithNoListenersCausesNoErrors(): void
    {
        $event  = new OrderPlaced(1);
        $result = $this->dispatcher->dispatch($event);

        self::assertSame($event, $result);
    }

    public function testDispatchCallsMultipleListenersForSamePriority(): void
    {
        $log = [];

        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$log): void {
            $log[] = 'a';
        }, 5);

        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$log): void {
            $log[] = 'b';
        }, 5);

        $this->dispatcher->dispatch(new OrderPlaced(1));

        self::assertSame(['a', 'b'], $log);
    }

    public function testDispatchPassesCorrectEventToListener(): void
    {
        $received = null;
        $this->provider->addListener(OrderPlaced::class, static function (object $event) use (&$received): void {
            $received = $event;
        });

        $event = new OrderPlaced(42);
        $this->dispatcher->dispatch($event);

        self::assertSame($event, $received);
    }

    public function testStoppableEventPropagationStopsAfterFirstListener(): void
    {
        $log = [];

        $this->provider->addListener(UserRegistered::class, static function (object $event) use (&$log): void {
            /** @var UserRegistered $event */
            $log[] = 'listener1';
            $event->stopPropagation();
        });

        $this->provider->addListener(UserRegistered::class, static function (object $event) use (&$log): void {
            $log[] = 'listener2';
        });

        $event = new UserRegistered('stop@test.com');
        $this->dispatcher->dispatch($event);

        self::assertSame(['listener1'], $log);
        self::assertTrue($event->isPropagationStopped());
    }
}

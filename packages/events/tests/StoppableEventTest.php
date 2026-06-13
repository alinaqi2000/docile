<?php

declare(strict_types=1);

namespace Docile\Events\Tests;

use Docile\Events\Tests\Fixtures\UserRegistered;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Docile\Events\StoppableEvent;

#[CoversClass(StoppableEvent::class)]
final class StoppableEventTest extends TestCase
{
    public function testPropagationIsNotStoppedByDefault(): void
    {
        $event = new UserRegistered('test@example.com');

        self::assertFalse($event->isPropagationStopped());
    }

    public function testStopPropagationSetsFlagToTrue(): void
    {
        $event = new UserRegistered('test@example.com');
        $event->stopPropagation();

        self::assertTrue($event->isPropagationStopped());
    }

    public function testStopPropagationIsIdempotent(): void
    {
        $event = new UserRegistered('test@example.com');
        $event->stopPropagation();
        $event->stopPropagation();

        self::assertTrue($event->isPropagationStopped());
    }

    public function testEventDataIsPreservedAfterStopping(): void
    {
        $event = new UserRegistered('user@example.com');
        $event->stopPropagation();

        self::assertSame('user@example.com', $event->email);
        self::assertTrue($event->isPropagationStopped());
    }
}

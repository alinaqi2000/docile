<?php

declare(strict_types=1);

namespace Docile\Support\Tests;

use DateTimeImmutable;
use Docile\Support\Clock\FrozenClock;
use Docile\Support\Clock\SystemClock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[CoversClass(SystemClock::class)]
#[CoversClass(FrozenClock::class)]
final class ClockTest extends TestCase
{
    // ---------------------------------------------------------------- SystemClock

    public function testSystemClockImplementsClockInterface(): void
    {
        $clock = new SystemClock();
        // Verify via reflection so PHPStan cannot narrow the type ahead of the assertion
        $implements = class_implements($clock);
        self::assertContains(ClockInterface::class, $implements !== false ? $implements : []);
    }

    public function testSystemClockNowReturnsDateTimeImmutable(): void
    {
        $clock = new SystemClock();
        $now = $clock->now();
        self::assertSame(DateTimeImmutable::class, $now::class);
    }

    public function testSystemClockNowIsCloseToCurrentTime(): void
    {
        $before = new DateTimeImmutable();
        $clock = new SystemClock();
        $now = $clock->now();
        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before->getTimestamp(), $now->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $now->getTimestamp());
    }

    public function testSystemClockNowReturnsDifferentInstancesEachCall(): void
    {
        $clock = new SystemClock();
        $a = $clock->now();
        $b = $clock->now();

        self::assertNotSame($a, $b);
    }

    // ---------------------------------------------------------------- FrozenClock

    public function testFrozenClockImplementsClockInterface(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable());
        $implements = class_implements($clock);
        self::assertContains(ClockInterface::class, $implements !== false ? $implements : []);
    }

    public function testFrozenClockAlwaysReturnsSameTime(): void
    {
        $frozen = new DateTimeImmutable('2024-01-15 12:00:00');
        $clock = new FrozenClock($frozen);

        self::assertSame($frozen, $clock->now());
        self::assertSame($frozen, $clock->now());
    }

    public function testFrozenClockSetTo(): void
    {
        $initial = new DateTimeImmutable('2024-01-01');
        $clock = new FrozenClock($initial);

        self::assertSame($initial, $clock->now());

        $updated = new DateTimeImmutable('2024-06-15');
        $clock->setTo($updated);

        self::assertSame($updated, $clock->now());
    }

    public function testFrozenClockCanBeUsedInTests(): void
    {
        $frozenTime = new DateTimeImmutable('2024-03-15 09:30:00');
        $clock = new FrozenClock($frozenTime);

        // Simulate multiple calls — always returns the frozen time
        for ($i = 0; $i < 5; ++$i) {
            self::assertSame('2024-03-15 09:30:00', $clock->now()->format('Y-m-d H:i:s'));
        }
    }

    public function testFrozenClockSetToAllowsTimeTravel(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2024-01-01'));

        $t1 = $clock->now();
        $clock->setTo(new DateTimeImmutable('2025-12-31'));
        $t2 = $clock->now();

        self::assertLessThan($t2->getTimestamp(), $t1->getTimestamp());
    }
}

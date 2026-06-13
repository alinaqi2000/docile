<?php

declare(strict_types=1);

namespace Docile\Security\Tests\RateLimit;

use Docile\Security\RateLimit\RateLimiterInterface;
use Docile\Security\RateLimit\TokenBucketLimiter;
use Docile\Security\Tests\Fixtures\ArrayCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[CoversClass(TokenBucketLimiter::class)]
final class TokenBucketLimiterTest extends TestCase
{
    private RateLimiterInterface $limiter;
    private ArrayCache $cache;
    private TestClock $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new ArrayCache();
        $this->clock = new TestClock();
        $this->limiter = new TokenBucketLimiter($this->cache, $this->clock);
    }

    public function testAttemptReturnsTrueForFirstRequest(): void
    {
        $result = $this->limiter->attempt('key1', 5, 60);

        $this->assertTrue($result);
    }

    public function testAttemptReturnsTrueUntilLimitReached(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($this->limiter->attempt('key1', 5, 60));
        }

        $this->assertFalse($this->limiter->attempt('key1', 5, 60));
    }

    public function testAttemptReturnsFalseAfterLimitExceeded(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->attempt('key1', 5, 60);
        }

        $this->assertFalse($this->limiter->attempt('key1', 5, 60));
        $this->assertFalse($this->limiter->attempt('key1', 5, 60));
    }

    public function testAttemptResetsAfterDecayTime(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->attempt('key1', 5, 60);
        }

        $this->assertFalse($this->limiter->attempt('key1', 5, 60));

        $this->clock->advance(61);

        $this->assertTrue($this->limiter->attempt('key1', 5, 60));
    }

    public function testAttemptHandlesDifferentKeysIndependently(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->attempt('key1', 5, 60);
        }

        $this->assertFalse($this->limiter->attempt('key1', 5, 60));
        $this->assertTrue($this->limiter->attempt('key2', 5, 60));
    }

    public function testRemainingReturnsMaxAttemptsForNewKey(): void
    {
        $remaining = $this->limiter->remaining('key1', 5);

        $this->assertSame(5, $remaining);
    }

    public function testRemainingDecrementsWithEachAttempt(): void
    {
        $this->limiter->attempt('key1', 5, 60);
        $this->assertSame(4, $this->limiter->remaining('key1', 5));

        $this->limiter->attempt('key1', 5, 60);
        $this->assertSame(3, $this->limiter->remaining('key1', 5));
    }

    public function testRemainingReturnsZeroWhenLimitReached(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->attempt('key1', 5, 60);
        }

        $this->assertSame(0, $this->limiter->remaining('key1', 5));
    }

    public function testRemainingResetsAfterDecayTime(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->attempt('key1', 5, 60);
        }

        $this->assertSame(0, $this->limiter->remaining('key1', 5));

        $this->clock->advance(61);

        $this->assertSame(5, $this->limiter->remaining('key1', 5));
    }

    public function testResetClearsTheKey(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->attempt('key1', 5, 60);
        }

        $this->limiter->reset('key1');

        $this->assertSame(5, $this->limiter->remaining('key1', 5));
        $this->assertTrue($this->limiter->attempt('key1', 5, 60));
    }

    public function testResetDoesNotAffectOtherKeys(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->attempt('key1', 5, 60);
            $this->limiter->attempt('key2', 5, 60);
        }

        $this->limiter->reset('key1');

        $this->assertSame(5, $this->limiter->remaining('key1', 5));
        $this->assertSame(2, $this->limiter->remaining('key2', 5));
    }

    public function testAttemptWithMaxAttemptsOfOne(): void
    {
        $this->assertTrue($this->limiter->attempt('key1', 1, 60));
        $this->assertFalse($this->limiter->attempt('key1', 1, 60));
    }

    public function testAttemptWithLargeMaxAttempts(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $this->assertTrue($this->limiter->attempt('key1', 100, 60));
        }

        $this->assertFalse($this->limiter->attempt('key1', 100, 60));
    }

    public function testRemainingNeverGoesBelowZero(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->limiter->attempt('key1', 5, 60);
        }

        $remaining = $this->limiter->remaining('key1', 5);

        $this->assertSame(0, $remaining);
    }
}

final class TestClock implements ClockInterface
{
    private int $time = 1000;

    public function now(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('U', (string) $this->time) ?: new \DateTimeImmutable();
    }

    public function advance(int $seconds): void
    {
        $this->time += $seconds;
    }
}

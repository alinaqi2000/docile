<?php

declare(strict_types=1);

namespace Docile\Cache\Tests;

use DateTime;
use DateTimeImmutable;
use Docile\Cache\CacheItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function sleep;

#[CoversClass(CacheItem::class)]
final class CacheItemTest extends TestCase
{
    public function testGetKeyReturnsKey(): void
    {
        $item = new CacheItem('test-key');

        self::assertSame('test-key', $item->getKey());
    }

    public function testGetReturnsDefaultValue(): void
    {
        $item = new CacheItem('test-key');

        self::assertNull($item->get());
    }

    public function testGetReturnsSetValue(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');

        self::assertSame('value', $item->get());
    }

    public function testIsHitReturnsFalseByDefault(): void
    {
        $item = new CacheItem('test-key');

        self::assertFalse($item->isHit());
    }

    public function testIsHitReturnsTrueAfterMarkAsHit(): void
    {
        $item = new CacheItem('test-key');
        $item->markAsHit();

        self::assertTrue($item->isHit());
    }

    public function testSetReturnsSelf(): void
    {
        $item = new CacheItem('test-key');
        $result = $item->set('value');

        self::assertSame($item, $result);
    }

    public function testSetWithTtl(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value', 10);

        self::assertSame('value', $item->get());
        self::assertNotNull($item->getExpiration());
    }

    public function testExpiresAfterWithNull(): void
    {
        $item = new CacheItem('test-key');
        $item->expiresAfter(null);

        self::assertNull($item->getExpiration());
    }

    public function testExpiresAfterWithInt(): void
    {
        $item = new CacheItem('test-key');
        $item->expiresAfter(10);

        self::assertNotNull($item->getExpiration());
    }

    public function testExpiresAfterWithDateInterval(): void
    {
        $item = new CacheItem('test-key');
        $item->expiresAfter(new \DateInterval('PT10S'));

        self::assertNotNull($item->getExpiration());
    }

    public function testExpiresAtWithNull(): void
    {
        $item = new CacheItem('test-key');
        $item->expiresAt(null);

        self::assertNull($item->getExpiration());
    }

    public function testExpiresAtWithDateTime(): void
    {
        $item = new CacheItem('test-key');
        $expiration = new DateTime('+1 hour');
        $item->expiresAt($expiration);

        self::assertNotNull($item->getExpiration());
    }

    public function testExpiresAtWithDateTimeImmutable(): void
    {
        $item = new CacheItem('test-key');
        $expiration = new DateTimeImmutable('+1 hour');
        $item->expiresAt($expiration);

        self::assertNotNull($item->getExpiration());
    }

    public function testIsExpiredReturnsFalseWhenNoExpiration(): void
    {
        $item = new CacheItem('test-key');

        self::assertFalse($item->isExpired());
    }

    public function testIsExpiredReturnsFalseWhenNotExpired(): void
    {
        $item = new CacheItem('test-key');
        $item->expiresAfter(10);

        self::assertFalse($item->isExpired());
    }

    public function testIsExpiredReturnsTrueWhenExpired(): void
    {
        $item = new CacheItem('test-key');
        $item->expiresAfter(1);
        sleep(2);

        self::assertTrue($item->isExpired());
    }

    public function testHasValueReturnsFalseByDefault(): void
    {
        $item = new CacheItem('test-key');

        self::assertFalse($item->hasValue());
    }

    public function testHasValueReturnsTrueAfterSet(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');

        self::assertTrue($item->hasValue());
    }

    public function testGetExpirationReturnsNullByDefault(): void
    {
        $item = new CacheItem('test-key');

        self::assertNull($item->getExpiration());
    }

    public function testGetExpirationReturnsDateTimeImmutable(): void
    {
        $item = new CacheItem('test-key');
        $item->expiresAfter(10);

        self::assertInstanceOf(DateTimeImmutable::class, $item->getExpiration());
    }
}

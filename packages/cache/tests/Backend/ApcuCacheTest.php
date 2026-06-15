<?php

declare(strict_types=1);

namespace Docile\Cache\Tests\Backend;

use Docile\Cache\Backend\ApcuCache;
use Docile\Cache\CacheItem;
use Docile\Cache\Exception\CacheException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApcuCache::class)]
final class ApcuCacheTest extends TestCase
{
    private ApcuCache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('apcu')) {
            self::markTestSkipped('APCu extension is not loaded.');
        }

        $this->cache = new ApcuCache('test:');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (isset($this->cache)) {
            $this->cache->clear();
        }
    }

    public function testConstructorThrowsWhenApcuNotLoaded(): void
    {
        if (extension_loaded('apcu')) {
            self::markTestSkipped('APCu extension is loaded.');
        }

        $this->expectException(CacheException::class);

        new ApcuCache();
    }

    public function testGetItemReturnsMissForNonExistentKey(): void
    {
        $item = $this->cache->getItem('non-existent');

        self::assertFalse($item->isHit());
        self::assertNull($item->get());
    }

    public function testGetItemReturnsHitForExistentKey(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');
        $this->cache->save($item);

        $retrieved = $this->cache->getItem('test-key');

        self::assertTrue($retrieved->isHit());
        self::assertSame('value', $retrieved->get());
    }

    public function testGetItemsReturnsMultipleItems(): void
    {
        $item1 = new CacheItem('key1');
        $item1->set('value1');
        $this->cache->save($item1);

        $item2 = new CacheItem('key2');
        $item2->set('value2');
        $this->cache->save($item2);

        $items = iterator_to_array($this->cache->getItems(['key1', 'key2']));

        self::assertCount(2, $items);
        self::assertSame('value1', $items['key1']->get());
        self::assertSame('value2', $items['key2']->get());
    }

    public function testHasItemReturnsFalseForNonExistentKey(): void
    {
        self::assertFalse($this->cache->hasItem('non-existent'));
    }

    public function testHasItemReturnsTrueForExistentKey(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');
        $this->cache->save($item);

        self::assertTrue($this->cache->hasItem('test-key'));
    }

    public function testClearRemovesAllItems(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');
        $this->cache->save($item);

        $this->cache->clear();

        self::assertFalse($this->cache->hasItem('test-key'));
    }

    public function testDeleteItemRemovesItem(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');
        $this->cache->save($item);

        $this->cache->deleteItem('test-key');

        self::assertFalse($this->cache->hasItem('test-key'));
    }

    public function testDeleteItemsRemovesMultipleItems(): void
    {
        $item1 = new CacheItem('key1');
        $item1->set('value1');
        $this->cache->save($item1);

        $item2 = new CacheItem('key2');
        $item2->set('value2');
        $this->cache->save($item2);

        $this->cache->deleteItems(['key1', 'key2']);

        self::assertFalse($this->cache->hasItem('key1'));
        self::assertFalse($this->cache->hasItem('key2'));
    }

    public function testSaveReturnsTrue(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');

        self::assertTrue($this->cache->save($item));
    }

    public function testSaveDeferredAddsToDeferredQueue(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');

        self::assertTrue($this->cache->saveDeferred($item));
    }

    public function testCommitSavesDeferredItems(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');
        $this->cache->saveDeferred($item);

        $this->cache->commit();

        self::assertTrue($this->cache->hasItem('test-key'));
    }

    public function testTtlExpiresItem(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value', 1);
        $this->cache->save($item);

        sleep(2);

        $retrieved = $this->cache->getItem('test-key');

        self::assertFalse($retrieved->isHit());
    }

    public function testTtlDoesNotExpireItemBeforeTime(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value', 10);
        $this->cache->save($item);

        $retrieved = $this->cache->getItem('test-key');

        self::assertTrue($retrieved->isHit());
    }

    public function testComplexValueTypes(): void
    {
        $item = new CacheItem('test-key');
        $item->set(['array' => 'value']);
        $this->cache->save($item);

        $retrieved = $this->cache->getItem('test-key');

        self::assertSame(['array' => 'value'], $retrieved->get());
    }

    public function testObjectValueTypes(): void
    {
        $obj = new \stdClass();
        $obj->property = 'value';

        $item = new CacheItem('test-key');
        $item->set($obj);
        $this->cache->save($item);

        $retrieved = $this->cache->getItem('test-key');

        self::assertEquals($obj, $retrieved->get());
    }

    public function testPrefixIsApplied(): void
    {
        $cache1 = new ApcuCache('prefix1:');
        $cache2 = new ApcuCache('prefix2:');

        $item1 = new CacheItem('test-key');
        $item1->set('value1');
        $cache1->save($item1);

        $item2 = new CacheItem('test-key');
        $item2->set('value2');
        $cache2->save($item2);

        $retrieved1 = $cache1->getItem('test-key');
        $retrieved2 = $cache2->getItem('test-key');

        self::assertSame('value1', $retrieved1->get());
        self::assertSame('value2', $retrieved2->get());
    }
}

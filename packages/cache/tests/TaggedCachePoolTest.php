<?php

declare(strict_types=1);

namespace Docile\Cache\Tests;

use Docile\Cache\Backend\ArrayCache;
use Docile\Cache\CacheItem;
use Docile\Cache\TaggedCachePool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaggedCachePool::class)]
final class TaggedCachePoolTest extends TestCase
{
    private TaggedCachePool $cache;
    private ArrayCache $pool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pool = new ArrayCache();
        $this->cache = new TaggedCachePool($this->pool);
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

    public function testTaggedItemIsStored(): void
    {
        $taggedCache = $this->cache->tag('tag1', 'tag2');
        $item = new CacheItem('test-key');
        $item->set('value');
        $taggedCache->save($item);

        $retrieved = $this->cache->getItem('test-key');

        self::assertTrue($retrieved->isHit());
        self::assertSame('value', $retrieved->get());
    }

    public function testInvalidateTagsRemovesTaggedItems(): void
    {
        $taggedCache = $this->cache->tag('tag1');
        $item = new CacheItem('test-key');
        $item->set('value');
        $taggedCache->save($item);

        $this->cache->invalidateTags('tag1');

        $retrieved = $this->cache->getItem('test-key');

        self::assertFalse($retrieved->isHit());
    }

    public function testInvalidateTagsDoesNotRemoveUntaggedItems(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');
        $this->cache->save($item);

        $this->cache->invalidateTags('tag1');

        $retrieved = $this->cache->getItem('test-key');

        self::assertTrue($retrieved->isHit());
    }

    public function testInvalidateMultipleTags(): void
    {
        $taggedCache1 = $this->cache->tag('tag1');
        $item1 = new CacheItem('key1');
        $item1->set('value1');
        $taggedCache1->save($item1);

        $taggedCache2 = $this->cache->tag('tag2');
        $item2 = new CacheItem('key2');
        $item2->set('value2');
        $taggedCache2->save($item2);

        $this->cache->invalidateTags('tag1', 'tag2');

        self::assertFalse($this->cache->hasItem('key1'));
        self::assertFalse($this->cache->hasItem('key2'));
    }

    public function testItemWithMultipleTags(): void
    {
        $taggedCache = $this->cache->tag('tag1', 'tag2');
        $item = new CacheItem('test-key');
        $item->set('value');
        $taggedCache->save($item);

        $this->cache->invalidateTags('tag1');

        self::assertFalse($this->cache->hasItem('test-key'));
    }

    public function testInvalidateNonExistentTagReturnsTrue(): void
    {
        self::assertTrue($this->cache->invalidateTags('non-existent'));
    }

    public function testTagReturnsNewInstance(): void
    {
        $taggedCache = $this->cache->tag('tag1');

        self::assertNotSame($this->cache, $taggedCache);
    }

    public function testClearRemovesAllItemsIncludingTags(): void
    {
        $taggedCache = $this->cache->tag('tag1');
        $item = new CacheItem('test-key');
        $item->set('value');
        $taggedCache->save($item);

        $this->cache->clear();

        self::assertFalse($this->cache->hasItem('test-key'));
    }

    public function testDeleteItemRemovesItem(): void
    {
        $taggedCache = $this->cache->tag('tag1');
        $item = new CacheItem('test-key');
        $item->set('value');
        $taggedCache->save($item);

        $this->cache->deleteItem('test-key');

        self::assertFalse($this->cache->hasItem('test-key'));
    }

    public function testHasItemReturnsTrueForExistentKey(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');
        $this->cache->save($item);

        self::assertTrue($this->cache->hasItem('test-key'));
    }

    public function testHasItemReturnsFalseForNonExistentKey(): void
    {
        self::assertFalse($this->cache->hasItem('non-existent'));
    }
}

<?php

declare(strict_types=1);

namespace Docile\Cache\Tests\Backend;

use Docile\Cache\Backend\NullCache;
use Docile\Cache\CacheItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NullCache::class)]
final class NullCacheTest extends TestCase
{
    private NullCache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new NullCache();
    }

    public function testGetItemAlwaysReturnsMiss(): void
    {
        $item = $this->cache->getItem('any-key');

        self::assertFalse($item->isHit());
        self::assertNull($item->get());
    }

    public function testGetItemsAlwaysReturnsMisses(): void
    {
        $items = iterator_to_array($this->cache->getItems(['key1', 'key2']));

        self::assertCount(2, $items);
        self::assertFalse($items['key1']->isHit());
        self::assertFalse($items['key2']->isHit());
    }

    public function testHasItemAlwaysReturnsFalse(): void
    {
        self::assertFalse($this->cache->hasItem('any-key'));
    }

    public function testClearAlwaysReturnsTrue(): void
    {
        self::assertTrue($this->cache->clear());
    }

    public function testDeleteItemAlwaysReturnsTrue(): void
    {
        self::assertTrue($this->cache->deleteItem('any-key'));
    }

    public function testDeleteItemsAlwaysReturnsTrue(): void
    {
        self::assertTrue($this->cache->deleteItems(['key1', 'key2']));
    }

    public function testSaveAlwaysReturnsTrue(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');

        self::assertTrue($this->cache->save($item));
    }

    public function testSaveDeferredAlwaysReturnsTrue(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');

        self::assertTrue($this->cache->saveDeferred($item));
    }

    public function testCommitAlwaysReturnsTrue(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');
        $this->cache->saveDeferred($item);

        self::assertTrue($this->cache->commit());
    }

    public function testItemIsNotActuallyStored(): void
    {
        $item = new CacheItem('test-key');
        $item->set('value');
        $this->cache->save($item);

        $retrieved = $this->cache->getItem('test-key');

        self::assertFalse($retrieved->isHit());
    }
}

<?php

declare(strict_types=1);

namespace Docile\Cache;

use Docile\Cache\Backend\ArrayCache;
use Docile\Cache\Exception\InvalidArgumentException;

use function array_diff;
use function array_merge;
use function array_unique;
use function array_values;
use function is_array;
use function is_string;

/**
 * Cache pool with tagging support.
 */
final class TaggedCachePool extends AbstractCachePool
{
    private AbstractCachePool $pool;
    private ArrayCache $tagStore;
    /** @var list<string> */
    private array $currentTags = [];

    public function __construct(AbstractCachePool $pool)
    {
        $this->pool = $pool;
        $this->tagStore = new ArrayCache();
    }

    /**
     * Create a new tagged cache pool with the given tags.
     */
    public function tag(string ...$tags): self
    {
        $new = clone $this;
        $new->currentTags = array_values($tags);

        return $new;
    }

    /**
     * Invalidate all cache items with the given tags.
     */
    public function invalidateTags(string ...$tags): bool
    {
        $keysToDelete = [];

        foreach ($tags as $tag) {
            $tagItem = $this->tagStore->getItem($this->tagKey($tag));

            if ($tagItem->isHit()) {
                $taggedKeys = $tagItem->get();

                if (is_array($taggedKeys)) {
                    foreach ($taggedKeys as $key) {
                        if (is_string($key)) {
                            $keysToDelete[] = $key;
                        }
                    }
                }

                $this->tagStore->deleteItem($this->tagKey($tag));
            }
        }

        if ($keysToDelete === []) {
            return true;
        }

        return $this->pool->deleteItems(array_unique($keysToDelete));
    }

    protected function doGet(string $key): CacheItem|null
    {
        $item = $this->pool->getItem($key);

        if (!$item->isHit()) {
            return null;
        }

        if ($item instanceof CacheItem) {
            return $item;
        }

        return null;
    }

    protected function doSave(CacheItem $item): bool
    {
        if ($this->currentTags !== []) {
            foreach ($this->currentTags as $tag) {
                $tagKey = $this->tagKey($tag);
                $tagItem = $this->tagStore->getItem($tagKey);

                if ($tagItem->isHit()) {
                    $taggedKeys = $tagItem->get();

                    if (is_array($taggedKeys)) {
                        $taggedKeys[] = $item->getKey();
                        $stringKeys = [];

                        foreach ($taggedKeys as $k) {
                            if (is_string($k)) {
                                $stringKeys[] = $k;
                            }
                        }

                        $tagItem->set(array_values(array_unique($stringKeys)));
                    }
                } else {
                    $tagItem->set([$item->getKey()]);
                }

                $this->tagStore->save($tagItem);
            }
        }

        return $this->pool->save($item);
    }

    /**
     * @param list<string> $keys
     */
    protected function doDelete(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->removeKeyFromAllTags($key);
        }

        return $this->pool->deleteItems($keys);
    }

    protected function doClear(): bool
    {
        $this->tagStore->clear();

        return $this->pool->clear();
    }

    protected function doHas(string $key): bool
    {
        return $this->pool->hasItem($key);
    }

    private function tagKey(string $tag): string
    {
        return 'tag-' . $tag;
    }

    private function removeKeyFromAllTags(string $key): void
    {
        foreach ($this->currentTags as $tag) {
            $tagKey = $this->tagKey($tag);
            $tagItem = $this->tagStore->getItem($tagKey);

            if ($tagItem->isHit()) {
                $taggedKeys = $tagItem->get();

                if (is_array($taggedKeys)) {
                    $filteredKeys = [];

                    foreach ($taggedKeys as $k) {
                        if (is_string($k) && $k !== $key) {
                            $filteredKeys[] = $k;
                        }
                    }

                    if ($filteredKeys === []) {
                        $this->tagStore->deleteItem($tagKey);
                    } else {
                        $tagItem->set($filteredKeys);
                        $this->tagStore->save($tagItem);
                    }
                }
            }
        }
    }
}

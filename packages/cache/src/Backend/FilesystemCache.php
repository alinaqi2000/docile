<?php

declare(strict_types=1);

namespace Docile\Cache\Backend;

use Docile\Cache\AbstractCachePool;
use Docile\Cache\CacheItem;
use Docile\Cache\Exception\CacheException;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function is_array;
use function is_dir;
use function mkdir;
use function sha1;
use function serialize;
use function unlink;
use function unserialize;

/**
 * Filesystem cache backend storing items as serialized files.
 */
final class FilesystemCache extends AbstractCachePool
{
    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/\\');

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    protected function doGet(string $key): CacheItem|null
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return null;
        }

        $content = file_get_contents($filename);

        if ($content === false) {
            throw CacheException::readFailure($filename);
        }

        $data = unserialize($content);

        if (!is_array($data)) {
            return null;
        }

        if ($data['expires'] !== null && time() > $data['expires']) {
            @unlink($filename);

            return null;
        }

        $item = new CacheItem($key);
        $item->set($data['value']);

        if ($data['expires'] !== null && is_string($data['expires'])) {
            $expiration = \DateTimeImmutable::createFromFormat('U', $data['expires']);

            if ($expiration !== false) {
                $item->expiresAt($expiration);
            }
        }

        return $item;
    }

    protected function doSave(CacheItem $item): bool
    {
        $filename = $this->getFilename($item->getKey());
        $expires = $item->getExpiration();
        $expiresTimestamp = $expires !== null ? (int) $expires->format('U') : null;

        $data = [
            'value' => $item->get(),
            'expires' => $expiresTimestamp,
        ];

        $result = file_put_contents($filename, serialize($data));

        if ($result === false) {
            throw CacheException::writeFailure($filename);
        }

        return true;
    }

    /**
     * @param list<string> $keys
     */
    protected function doDelete(array $keys): bool
    {
        foreach ($keys as $key) {
            $filename = $this->getFilename($key);

            if (file_exists($filename)) {
                @unlink($filename);
            }
        }

        return true;
    }

    protected function doClear(): bool
    {
        $files = glob($this->directory . '/*.cache');

        if ($files === false) {
            return true;
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }

    protected function doHas(string $key): bool
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return false;
        }

        $content = file_get_contents($filename);

        if ($content === false) {
            return false;
        }

        $data = unserialize($content);

        if (!is_array($data)) {
            return false;
        }

        if ($data['expires'] !== null && is_string($data['expires']) && time() > (int) $data['expires']) {
            @unlink($filename);

            return false;
        }

        return true;
    }

    private function getFilename(string $key): string
    {
        return $this->directory . '/' . sha1($key) . '.cache';
    }
}

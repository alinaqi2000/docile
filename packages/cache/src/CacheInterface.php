<?php

declare(strict_types=1);

namespace Docile\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;

/**
 * Unified cache interface extending both PSR-6 and PSR-16.
 */
interface CacheInterface extends CacheItemPoolInterface, SimpleCacheInterface {}

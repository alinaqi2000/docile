<?php

declare(strict_types=1);

namespace Docile\Container\Tests\Fixtures;

use Docile\Container\Attribute\Singleton;

#[Singleton]
final class SingletonMarked {}

<?php

declare(strict_types=1);

namespace Docile\Config\Tests;

use Docile\Config\ArrayLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayLoader::class)]
final class ArrayLoaderTest extends TestCase
{
    public function testLoadReturnsGivenArray(): void
    {
        $data = ['foo' => 'bar', 'baz' => 42];
        $loader = new ArrayLoader($data);

        self::assertSame($data, $loader->load());
    }

    public function testLoadReturnsEmptyArrayWhenConstructedWithNoData(): void
    {
        $loader = new ArrayLoader([]);

        self::assertSame([], $loader->load());
    }

    public function testLoadReturnsSameDataOnMultipleCalls(): void
    {
        $data = ['key' => 'value'];
        $loader = new ArrayLoader($data);

        self::assertSame($loader->load(), $loader->load());
    }

    public function testLoadReturnsNestedArray(): void
    {
        $data = ['db' => ['host' => 'localhost', 'port' => 3306]];
        $loader = new ArrayLoader($data);

        self::assertSame($data, $loader->load());
    }
}

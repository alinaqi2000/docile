<?php

declare(strict_types=1);

namespace Docile\Config\Tests;

use Docile\Config\ArrayLoader;
use Docile\Config\ChainLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChainLoader::class)]
final class ChainLoaderTest extends TestCase
{
    public function testLoadMergesMultipleLoadersInOrder(): void
    {
        $first = new ArrayLoader(['foo' => 'first', 'shared' => 'first']);
        $second = new ArrayLoader(['bar' => 'second', 'shared' => 'second']);

        $chain = new ChainLoader($first, $second);
        $data = $chain->load();

        self::assertSame('first', $data['foo']);
        self::assertSame('second', $data['bar']);
        // Later loader wins on conflict
        self::assertSame('second', $data['shared']);
    }

    public function testLoadWithNoLoadersReturnsEmptyArray(): void
    {
        $chain = new ChainLoader();
        self::assertSame([], $chain->load());
    }

    public function testLoadWithSingleLoaderReturnsSameData(): void
    {
        $data = ['key' => 'value'];
        $chain = new ChainLoader(new ArrayLoader($data));

        self::assertSame($data, $chain->load());
    }

    public function testLoadDeepMergesNestedArrays(): void
    {
        $first = new ArrayLoader(['db' => ['host' => 'localhost', 'port' => 3306]]);
        $second = new ArrayLoader(['db' => ['port' => 5432, 'name' => 'mydb']]);

        $chain = new ChainLoader($first, $second);
        $data = $chain->load();

        // Deep merge: host preserved, port overridden, name added
        self::assertIsArray($data['db']);
        $db = $data['db'];
        self::assertSame('localhost', $db['host']);
        self::assertSame(5432, $db['port']);
        self::assertSame('mydb', $db['name']);
    }

    public function testLoadLaterLoaderWinsOnScalarConflict(): void
    {
        $first = new ArrayLoader(['key' => 'first']);
        $second = new ArrayLoader(['key' => 'second']);
        $third = new ArrayLoader(['key' => 'third']);

        $chain = new ChainLoader($first, $second, $third);
        $data = $chain->load();

        self::assertSame('third', $data['key']);
    }

    public function testLoadOverridesNestedArrayWithScalar(): void
    {
        $first = new ArrayLoader(['key' => ['nested' => 'value']]);
        $second = new ArrayLoader(['key' => 'scalar']);

        $chain = new ChainLoader($first, $second);
        $data = $chain->load();

        self::assertSame('scalar', $data['key']);
    }
}

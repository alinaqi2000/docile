<?php

declare(strict_types=1);

namespace Docile\Config\Tests;

use Docile\Config\ArrayLoader;
use Docile\Config\Exception\MissingKeyException;
use Docile\Config\Exception\TypeMismatchException;
use Docile\Config\Repository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Repository::class)]
final class RepositoryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction & all()
    // -------------------------------------------------------------------------

    public function testConstructWithEmptyArrayReturnsEmptyAll(): void
    {
        $repo = new Repository();
        self::assertSame([], $repo->all());
    }

    public function testConstructWithItemsPreservesItems(): void
    {
        $items = ['foo' => 'bar', 'nested' => ['a' => 1]];
        $repo = new Repository($items);
        self::assertSame($items, $repo->all());
    }

    // -------------------------------------------------------------------------
    // get() / has() / set()
    // -------------------------------------------------------------------------

    public function testGetReturnsTopLevelValue(): void
    {
        $repo = new Repository(['name' => 'Docile']);
        self::assertSame('Docile', $repo->get('name'));
    }

    public function testGetReturnsDotNotationNestedValue(): void
    {
        $repo = new Repository(['db' => ['host' => 'localhost']]);
        self::assertSame('localhost', $repo->get('db.host'));
    }

    public function testGetReturnsDefaultWhenKeyMissing(): void
    {
        $repo = new Repository();
        self::assertSame('default', $repo->get('missing', 'default'));
    }

    public function testGetReturnsNullDefaultByDefault(): void
    {
        $repo = new Repository();
        self::assertNull($repo->get('missing'));
    }

    public function testGetReturnsDefaultForPartiallyMissingPath(): void
    {
        $repo = new Repository(['a' => ['b' => 1]]);
        self::assertSame('fallback', $repo->get('a.c', 'fallback'));
    }

    public function testGetReturnsDeeplyNestedValue(): void
    {
        $repo = new Repository(['a' => ['b' => ['c' => 'deep']]]);
        self::assertSame('deep', $repo->get('a.b.c'));
    }

    public function testHasReturnsTrueForExistingTopLevelKey(): void
    {
        $repo = new Repository(['key' => 'value']);
        self::assertTrue($repo->has('key'));
    }

    public function testHasReturnsTrueForExistingNestedKey(): void
    {
        $repo = new Repository(['db' => ['host' => 'localhost']]);
        self::assertTrue($repo->has('db.host'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $repo = new Repository();
        self::assertFalse($repo->has('missing'));
    }

    public function testHasReturnsFalseForPartialPath(): void
    {
        $repo = new Repository(['a' => ['b' => 1]]);
        self::assertFalse($repo->has('a.c'));
    }

    public function testSetCreatesTopLevelKey(): void
    {
        $repo = new Repository();
        $repo->set('foo', 'bar');
        self::assertSame('bar', $repo->get('foo'));
    }

    public function testSetCreatesNestedKey(): void
    {
        $repo = new Repository();
        $repo->set('db.host', 'localhost');
        self::assertSame('localhost', $repo->get('db.host'));
    }

    public function testSetOverwritesExistingValue(): void
    {
        $repo = new Repository(['key' => 'old']);
        $repo->set('key', 'new');
        self::assertSame('new', $repo->get('key'));
    }

    public function testSetCreatesIntermediateArraysForDeepPath(): void
    {
        $repo = new Repository();
        $repo->set('a.b.c', 42);
        self::assertSame(42, $repo->get('a.b.c'));
    }

    public function testSetOverwritesNestedArrayWithScalar(): void
    {
        $repo = new Repository(['key' => ['nested' => 'value']]);
        $repo->set('key', 'scalar');
        self::assertSame('scalar', $repo->get('key'));
    }

    // -------------------------------------------------------------------------
    // load()
    // -------------------------------------------------------------------------

    public function testLoadMergesLoaderDataIntoRepository(): void
    {
        $repo = new Repository(['existing' => 'yes']);
        $repo->load(new ArrayLoader(['new' => 'value']));

        self::assertSame('yes', $repo->get('existing'));
        self::assertSame('value', $repo->get('new'));
    }

    // -------------------------------------------------------------------------
    // merge()
    // -------------------------------------------------------------------------

    public function testMergeAddsNewKeys(): void
    {
        $repo = new Repository(['a' => 1]);
        $repo->merge(['b' => 2]);

        self::assertSame(1, $repo->get('a'));
        self::assertSame(2, $repo->get('b'));
    }

    public function testMergeOverwritesScalarWithLaterValue(): void
    {
        $repo = new Repository(['key' => 'old']);
        $repo->merge(['key' => 'new']);

        self::assertSame('new', $repo->get('key'));
    }

    public function testMergeDeepMergesNestedArrays(): void
    {
        $repo = new Repository(['db' => ['host' => 'localhost', 'port' => 3306]]);
        $repo->merge(['db' => ['port' => 5432, 'name' => 'mydb']]);

        self::assertSame('localhost', $repo->get('db.host'));
        self::assertSame(5432, $repo->get('db.port'));
        self::assertSame('mydb', $repo->get('db.name'));
    }

    // -------------------------------------------------------------------------
    // Typed reads — happy paths
    // -------------------------------------------------------------------------

    public function testStringReturnsStringValue(): void
    {
        $repo = new Repository(['key' => 'hello']);
        self::assertSame('hello', $repo->string('key'));
    }

    public function testStringReturnsDefaultWhenKeyMissing(): void
    {
        $repo = new Repository();
        self::assertSame('fallback', $repo->string('missing', 'fallback'));
    }

    public function testStringDefaultIsEmptyString(): void
    {
        $repo = new Repository();
        self::assertSame('', $repo->string('missing'));
    }

    public function testIntReturnsIntValue(): void
    {
        $repo = new Repository(['count' => 5]);
        self::assertSame(5, $repo->int('count'));
    }

    public function testIntReturnsDefaultWhenKeyMissing(): void
    {
        $repo = new Repository();
        self::assertSame(99, $repo->int('missing', 99));
    }

    public function testIntDefaultIsZero(): void
    {
        $repo = new Repository();
        self::assertSame(0, $repo->int('missing'));
    }

    public function testFloatReturnsFloatValue(): void
    {
        $repo = new Repository(['rate' => 1.5]);
        self::assertSame(1.5, $repo->float('rate'));
    }

    public function testFloatAcceptsIntAsFloat(): void
    {
        $repo = new Repository(['rate' => 2]);
        self::assertSame(2.0, $repo->float('rate'));
    }

    public function testFloatReturnsDefaultWhenKeyMissing(): void
    {
        $repo = new Repository();
        self::assertSame(3.14, $repo->float('missing', 3.14));
    }

    public function testFloatDefaultIsZero(): void
    {
        $repo = new Repository();
        self::assertSame(0.0, $repo->float('missing'));
    }

    public function testBoolReturnsBoolValue(): void
    {
        $repo = new Repository(['debug' => true]);
        self::assertTrue($repo->bool('debug'));
    }

    public function testBoolReturnsFalseValue(): void
    {
        $repo = new Repository(['debug' => false]);
        self::assertFalse($repo->bool('debug'));
    }

    public function testBoolReturnsDefaultWhenKeyMissing(): void
    {
        $repo = new Repository();
        self::assertTrue($repo->bool('missing', true));
    }

    public function testBoolDefaultIsFalse(): void
    {
        $repo = new Repository();
        self::assertFalse($repo->bool('missing'));
    }

    public function testArrayReturnsArrayValue(): void
    {
        $repo = new Repository(['list' => ['a', 'b', 'c']]);
        self::assertSame(['a', 'b', 'c'], $repo->array('list'));
    }

    public function testArrayReturnsDefaultWhenKeyMissing(): void
    {
        $repo = new Repository();
        $default = ['x' => 1];
        self::assertSame($default, $repo->array('missing', $default));
    }

    public function testArrayDefaultIsEmptyArray(): void
    {
        $repo = new Repository();
        self::assertSame([], $repo->array('missing'));
    }

    // -------------------------------------------------------------------------
    // Typed reads — TypeMismatchException paths
    // -------------------------------------------------------------------------

    public function testStringThrowsTypeMismatchWhenValueIsNotString(): void
    {
        $repo = new Repository(['key' => 42]);

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('key');

        $repo->string('key');
    }

    public function testIntThrowsTypeMismatchWhenValueIsNotInt(): void
    {
        $repo = new Repository(['key' => 'oops']);

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('key');

        $repo->int('key');
    }

    public function testFloatThrowsTypeMismatchWhenValueIsNotNumeric(): void
    {
        $repo = new Repository(['key' => 'oops']);

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('key');

        $repo->float('key');
    }

    public function testBoolThrowsTypeMismatchWhenValueIsNotBool(): void
    {
        $repo = new Repository(['key' => 1]);

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('key');

        $repo->bool('key');
    }

    public function testArrayThrowsTypeMismatchWhenValueIsNotArray(): void
    {
        $repo = new Repository(['key' => 'string']);

        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('key');

        $repo->array('key');
    }

    // -------------------------------------------------------------------------
    // required()
    // -------------------------------------------------------------------------

    public function testRequiredReturnsValueWhenKeyExists(): void
    {
        $repo = new Repository(['key' => 'value']);
        self::assertSame('value', $repo->required('key'));
    }

    public function testRequiredReturnsMixedTypes(): void
    {
        $repo = new Repository(['num' => 42, 'flag' => true]);
        self::assertSame(42, $repo->required('num'));
        self::assertTrue($repo->required('flag'));
    }

    public function testRequiredThrowsMissingKeyExceptionWhenKeyAbsent(): void
    {
        $repo = new Repository();

        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage('missing.key');

        $repo->required('missing.key');
    }

    public function testRequiredWorksWithDotNotation(): void
    {
        $repo = new Repository(['db' => ['host' => 'localhost']]);
        self::assertSame('localhost', $repo->required('db.host'));
    }

    // -------------------------------------------------------------------------
    // ArrayAccess
    // -------------------------------------------------------------------------

    public function testOffsetExistsReturnsTrueForExistingKey(): void
    {
        $repo = new Repository(['key' => 'value']);
        self::assertTrue(isset($repo['key']));
    }

    public function testOffsetExistsReturnsFalseForMissingKey(): void
    {
        $repo = new Repository();
        self::assertFalse(isset($repo['missing']));
    }

    public function testOffsetGetReturnsValue(): void
    {
        $repo = new Repository(['key' => 'hello']);
        self::assertSame('hello', $repo['key']);
    }

    public function testOffsetGetReturnsDotNotationValue(): void
    {
        $repo = new Repository(['a' => ['b' => 'deep']]);
        self::assertSame('deep', $repo['a.b']);
    }

    public function testOffsetSetWritesValue(): void
    {
        $repo = new Repository();
        $repo['key'] = 'value';
        self::assertSame('value', $repo->get('key'));
    }

    public function testOffsetSetWritesDotNotationKey(): void
    {
        $repo = new Repository();
        $repo['db.host'] = 'localhost';
        self::assertSame('localhost', $repo->get('db.host'));
    }

    public function testOffsetUnsetRemovesTopLevelKey(): void
    {
        $repo = new Repository(['key' => 'value', 'other' => 'stays']);
        unset($repo['key']);

        self::assertFalse($repo->has('key'));
        self::assertTrue($repo->has('other'));
    }

    public function testOffsetUnsetRemovesNestedKey(): void
    {
        $repo = new Repository(['db' => ['host' => 'localhost', 'port' => 3306]]);
        unset($repo['db.host']);

        self::assertFalse($repo->has('db.host'));
        self::assertTrue($repo->has('db.port'));
    }

    public function testOffsetUnsetOnMissingKeyDoesNotThrow(): void
    {
        $repo = new Repository();
        unset($repo['nonexistent']);
        self::assertSame([], $repo->all());
    }

    public function testOffsetUnsetOnMissingNestedPathDoesNotThrow(): void
    {
        $repo = new Repository(['a' => 'scalar']);
        unset($repo['a.b.c']);
        self::assertSame(['a' => 'scalar'], $repo->all());
    }
}

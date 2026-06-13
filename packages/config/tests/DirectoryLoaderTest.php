<?php

declare(strict_types=1);

namespace Docile\Config\Tests;

use Docile\Config\DirectoryLoader;
use Docile\Config\Exception\LoaderException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DirectoryLoader::class)]
final class DirectoryLoaderTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/Fixtures';
    }

    public function testLoadReturnsKeyedByFilename(): void
    {
        $loader = new DirectoryLoader($this->fixturesDir);
        $data = $loader->load();

        self::assertArrayHasKey('app', $data);
        self::assertArrayHasKey('database', $data);
    }

    public function testLoadReturnsCorrectContentsForApp(): void
    {
        $loader = new DirectoryLoader($this->fixturesDir);
        $data = $loader->load();

        self::assertIsArray($data['app']);
        $app = $data['app'];
        self::assertSame('Docile', $app['name']);
        self::assertTrue($app['debug']);
    }

    public function testLoadReturnsCorrectContentsForDatabase(): void
    {
        $loader = new DirectoryLoader($this->fixturesDir);
        $data = $loader->load();

        self::assertIsArray($data['database']);
        $database = $data['database'];
        self::assertSame('sqlite', $database['driver']);
        self::assertSame(':memory:', $database['path']);
    }

    public function testLoadThrowsLoaderExceptionWhenDirectoryNotFound(): void
    {
        $loader = new DirectoryLoader('/nonexistent/directory');

        $this->expectException(LoaderException::class);
        $this->expectExceptionMessage('/nonexistent/directory');

        $loader->load();
    }

    public function testLoadThrowsLoaderExceptionWhenFileInDirectoryDoesNotReturnArray(): void
    {
        $tmpDir = sys_get_temp_dir() . '/docile_cfg_test_' . uniqid('', true);
        mkdir($tmpDir);
        $tmpFile = $tmpDir . '/bad.php';
        file_put_contents($tmpFile, '<?php return "not an array";');

        try {
            $loader = new DirectoryLoader($tmpDir);

            $this->expectException(LoaderException::class);
            $this->expectExceptionMessage($tmpFile);

            $loader->load();
        } finally {
            @unlink($tmpFile);
            @rmdir($tmpDir);
        }
    }

    public function testLoadReturnsEmptyArrayForEmptyDirectory(): void
    {
        $tmpDir = sys_get_temp_dir() . '/docile_cfg_test_empty_' . uniqid('', true);
        mkdir($tmpDir);

        try {
            $loader = new DirectoryLoader($tmpDir);
            $data = $loader->load();

            self::assertSame([], $data);
        } finally {
            @rmdir($tmpDir);
        }
    }
}

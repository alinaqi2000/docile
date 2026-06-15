<?php

declare(strict_types=1);

namespace Docile\Config\Tests;

use Docile\Config\Exception\LoaderException;
use Docile\Config\PhpFileLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpFileLoader::class)]
final class PhpFileLoaderTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/Fixtures';
    }

    public function testLoadReturnsArrayFromPhpFile(): void
    {
        $loader = new PhpFileLoader($this->fixturesDir . '/app.php');
        $data = $loader->load();

        self::assertSame('Docile', $data['name']);
        self::assertTrue($data['debug']);
    }

    public function testLoadReturnsDatabaseFixture(): void
    {
        $loader = new PhpFileLoader($this->fixturesDir . '/database.php');
        $data = $loader->load();

        self::assertSame('sqlite', $data['driver']);
        self::assertSame(':memory:', $data['path']);
    }

    public function testLoadThrowsLoaderExceptionWhenFileNotFound(): void
    {
        $loader = new PhpFileLoader('/nonexistent/path/config.php');

        $this->expectException(LoaderException::class);
        $this->expectExceptionMessage('/nonexistent/path/config.php');

        $loader->load();
    }

    public function testLoadThrowsLoaderExceptionWhenFileDoesNotReturnArray(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'docile_cfg_test_') . '.php';
        file_put_contents($tmpFile, '<?php return "not an array";');

        try {
            $loader = new PhpFileLoader($tmpFile);

            $this->expectException(LoaderException::class);
            $this->expectExceptionMessage($tmpFile);

            $loader->load();
        } finally {
            @unlink($tmpFile);
        }
    }
}

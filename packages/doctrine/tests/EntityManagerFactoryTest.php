<?php

declare(strict_types=1);

namespace Docile\Doctrine\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Docile\Doctrine\EntityManagerFactory;
use Doctrine\ORM\EntityManagerInterface;

#[CoversClass(EntityManagerFactory::class)]
final class EntityManagerFactoryTest extends TestCase
{
    private string $proxyDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proxyDir = sys_get_temp_dir() . '/docile_doctrine_proxies';
        if (!is_dir($this->proxyDir)) {
            mkdir($this->proxyDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $files = glob($this->proxyDir . '/*.php');
        if ($files !== false) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public function testCreateReturnsEntityManagerInstance(): void
    {
        $config = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $em = EntityManagerFactory::create(
            $config,
            __DIR__ . '/Fixtures/Entity',
            $this->proxyDir
        );

        $this->assertNotNull($em);
    }

    public function testCreateWithMysqlDriver(): void
    {
        $config = [
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'port' => 3306,
            'dbname' => 'test_db',
            'user' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ];

        $em = EntityManagerFactory::create(
            $config,
            __DIR__ . '/Fixtures/Entity',
            $this->proxyDir
        );

        $this->assertNotNull($em);
    }

    public function testCreateWithPostgresDriver(): void
    {
        $config = [
            'driver' => 'pdo_pgsql',
            'host' => 'localhost',
            'port' => 5432,
            'dbname' => 'test_db',
            'user' => 'postgres',
            'password' => '',
            'charset' => 'utf8',
        ];

        $em = EntityManagerFactory::create(
            $config,
            __DIR__ . '/Fixtures/Entity',
            $this->proxyDir
        );

        $this->assertNotNull($em);
    }

    public function testCreateWithSqliteDriver(): void
    {
        $config = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $em = EntityManagerFactory::create(
            $config,
            __DIR__ . '/Fixtures/Entity',
            $this->proxyDir
        );

        $this->assertNotNull($em);
    }
}

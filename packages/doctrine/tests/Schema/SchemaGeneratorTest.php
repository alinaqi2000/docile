<?php

declare(strict_types=1);

namespace Docile\Doctrine\Tests\Schema;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Docile\Doctrine\Schema\SchemaGenerator;
use Docile\Doctrine\Tests\Fixtures\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

#[CoversClass(SchemaGenerator::class)]
final class SchemaGeneratorTest extends TestCase
{
    private ?EntityManagerInterface $em;
    private SchemaGenerator $generator;
    private string $proxyDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proxyDir = sys_get_temp_dir() . '/docile_doctrine_proxies';
        if (!is_dir($this->proxyDir)) {
            mkdir($this->proxyDir, 0777, true);
        }

        $config = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $this->em = \Docile\Doctrine\EntityManagerFactory::create(
            $config,
            __DIR__ . '/../Fixtures/Entity',
            $this->proxyDir
        );

        $this->generator = new SchemaGenerator($this->em);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->em->close();
        $this->em = null;

        $files = glob($this->proxyDir . '/*.php');
        if ($files !== false) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public function testGenerateCreatesTables(): void
    {
        $this->generator->generate([User::class]);

        $schemaManager = $this->em->getConnection()->createSchemaManager();
        $this->assertTrue($schemaManager->tablesExist(['users']));
    }

    public function testGenerateWithMultipleEntities(): void
    {
        $this->generator->generate([User::class]);

        $schemaManager = $this->em->getConnection()->createSchemaManager();
        $this->assertTrue($schemaManager->tablesExist(['users']));
    }

    public function testDropRemovesTables(): void
    {
        $this->generator->generate([User::class]);

        $schemaManager = $this->em->getConnection()->createSchemaManager();
        $this->assertTrue($schemaManager->tablesExist(['users']));

        $this->generator->drop([User::class]);

        $this->assertFalse($schemaManager->tablesExist(['users']));
    }

    public function testDropWithMultipleEntities(): void
    {
        $this->generator->generate([User::class]);

        $this->generator->drop([User::class]);

        $schemaManager = $this->em->getConnection()->createSchemaManager();
        $this->assertFalse($schemaManager->tablesExist(['users']));
    }

    public function testGenerateIsIdempotent(): void
    {
        $this->generator->generate([User::class]);
        $this->generator->generate([User::class]);

        $schemaManager = $this->em->getConnection()->createSchemaManager();
        $this->assertTrue($schemaManager->tablesExist(['users']));
    }
}

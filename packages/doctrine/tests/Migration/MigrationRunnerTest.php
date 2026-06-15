<?php

declare(strict_types=1);

namespace Docile\Doctrine\Tests\Migration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Docile\Doctrine\Migration\MigrationRunner;
use Docile\Doctrine\Tests\Fixtures\Migrations\CreateUsersTable;
use Doctrine\ORM\EntityManagerInterface;

#[CoversClass(MigrationRunner::class)]
final class MigrationRunnerTest extends TestCase
{
    private ?EntityManagerInterface $em;
    private MigrationRunner $runner;
    private string $dbFile;
    private string $proxyDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbFile = sys_get_temp_dir() . '/docile_doctrine_test.db';
        $this->proxyDir = sys_get_temp_dir() . '/docile_doctrine_proxies';

        if (!is_dir($this->proxyDir)) {
            mkdir($this->proxyDir, 0777, true);
        }

        if (file_exists($this->dbFile)) {
            unlink($this->dbFile);
        }

        $config = [
            'driver' => 'pdo_sqlite',
            'path' => $this->dbFile,
        ];

        $this->em = \Docile\Doctrine\EntityManagerFactory::create(
            $config,
            __DIR__ . '/../Fixtures/Entity',
            $this->proxyDir
        );

        $this->runner = new MigrationRunner(
            $this->em,
            __DIR__ . '/../Fixtures/Migrations'
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->em !== null) {
            $this->em->close();
            $this->em = null;
        }

        if (file_exists($this->dbFile)) {
            unlink($this->dbFile);
        }

        $files = glob($this->proxyDir . '/*.php');
        if ($files !== false) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public function testRunExecutesPendingMigrations(): void
    {
        $count = $this->runner->run();

        self::assertSame(1, $count);

        if ($this->em === null) {
            throw new \RuntimeException('EntityManager not initialized');
        }

        $schemaManager = $this->em->getConnection()->createSchemaManager();
        self::assertTrue($schemaManager->tablesExist(['users']));
    }

    public function testRunReturnsZeroWhenNoPendingMigrations(): void
    {
        $this->runner->run();

        $count = $this->runner->run();

        self::assertSame(0, $count);
    }

    public function testStatusReturnsMigrationStatus(): void
    {
        $status = $this->runner->status();

        self::assertArrayHasKey(CreateUsersTable::class, $status);
        self::assertFalse($status[CreateUsersTable::class]);

        $this->runner->run();

        $status = $this->runner->status();

        self::assertTrue($status[CreateUsersTable::class]);
    }

    public function testRollbackRevertsLastMigration(): void
    {
        $this->runner->run();

        if ($this->em === null) {
            throw new \RuntimeException('EntityManager not initialized');
        }

        $schemaManager = $this->em->getConnection()->createSchemaManager();
        self::assertTrue($schemaManager->tablesExist(['users']));

        $count = $this->runner->rollback();

        self::assertSame(1, $count);
    }

    public function testRollbackReturnsZeroWhenNoMigrationsToRollback(): void
    {
        $count = $this->runner->rollback();

        self::assertSame(0, $count);
    }

    public function testRollbackWithSteps(): void
    {
        $this->runner->run();

        $count = $this->runner->rollback(1);

        self::assertSame(1, $count);
    }

    public function testRunRecordsMigrationInTrackingTable(): void
    {
        $this->runner->run();

        if ($this->em === null) {
            throw new \RuntimeException('EntityManager not initialized');
        }

        $qb = $this->em->getConnection()->createQueryBuilder();
        $result = $qb->select('migration_class')
            ->from('_doctrine_migrations')
            ->executeQuery();

        $migrations = $result->fetchFirstColumn();

        self::assertContains(CreateUsersTable::class, $migrations);
    }

    public function testRollbackRemovesMigrationFromTrackingTable(): void
    {
        $this->runner->run();
        $this->runner->rollback();

        if ($this->em === null) {
            throw new \RuntimeException('EntityManager not initialized');
        }

        $qb = $this->em->getConnection()->createQueryBuilder();
        $result = $qb->select('COUNT(*)')
            ->from('_doctrine_migrations')
            ->executeQuery();

        $count = $result->fetchOne();

        self::assertSame(0, $count);
    }
}

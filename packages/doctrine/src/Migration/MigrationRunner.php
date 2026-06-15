<?php

declare(strict_types=1);

namespace Docile\Doctrine\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Docile\Doctrine\Exception\MigrationException;

final class MigrationRunner
{
    private const MIGRATIONS_TABLE = '_doctrine_migrations';

    private Connection $connection;

    /**
     * @param EntityManagerInterface $em
     * @param string $migrationsPath
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $migrationsPath
    ) {
        $this->connection = $em->getConnection();
    }

    /**
     * @return int Number of migrations run
     */
    public function run(): int
    {
        $this->ensureMigrationsTableExists();

        $migrations = $this->discoverMigrations();
        $executed = $this->getExecutedMigrations();
        $pending = array_diff($migrations, $executed);

        if ($pending === []) {
            return 0;
        }

        $count = 0;
        foreach ($pending as $migrationClass) {
            $this->executeMigration($migrationClass, 'up');
            $this->recordMigration($migrationClass);
            $count++;
        }

        return $count;
    }

    /**
     * @param int $steps Number of migrations to rollback
     * @return int Number of migrations rolled back
     */
    public function rollback(int $steps = 1): int
    {
        $this->ensureMigrationsTableExists();

        $executed = $this->getExecutedMigrations();
        $toRollback = array_slice(array_reverse($executed), 0, $steps);

        if ($toRollback === []) {
            return 0;
        }

        $count = 0;
        foreach ($toRollback as $migrationClass) {
            $this->executeMigration($migrationClass, 'down');
            $this->removeMigration($migrationClass);
            $count++;
        }

        return $count;
    }

    /**
     * @return array<string,bool> Migration class name => executed status
     */
    public function status(): array
    {
        $this->ensureMigrationsTableExists();

        $migrations = $this->discoverMigrations();
        $executed = $this->getExecutedMigrations();

        $status = [];
        foreach ($migrations as $migration) {
            $status[$migration] = in_array($migration, $executed, true);
        }

        return $status;
    }

    /**
     * @return list<string> List of migration class names
     */
    private function discoverMigrations(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php');
        if ($files === false) {
            return [];
        }

        $migrations = [];
        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);
            if ($className !== null && $this->isMigrationClass($className)) {
                $migrations[] = $className;
            }
        }

        sort($migrations);

        return $migrations;
    }

    private function getFileNameFromClass(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts) . '.php';
    }

    private function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $namespaceMatch = preg_match('/namespace\s+([^;]+);/', $content, $matches);
        if ($namespaceMatch === 0 || $namespaceMatch === false) {
            return null;
        }

        $namespace = trim($matches[1]);

        $classMatch = preg_match('/class\s+(\w+)/', $content, $matches);
        if ($classMatch === 0 || $classMatch === false) {
            return null;
        }

        $className = trim($matches[1]);
        return $namespace . '\\' . $className;
    }

    private function isMigrationClass(string $className): bool
    {
        if (!class_exists($className)) {
            require_once $this->migrationsPath . '/' . $this->getFileNameFromClass($className);
        }

        if (!class_exists($className)) {
            return false;
        }

        $implements = class_implements($className);
        if ($implements === false) {
            return false;
        }

        return in_array(MigrationInterface::class, $implements, true);
    }

    /**
     * @return list<string> List of executed migration class names
     */
    private function getExecutedMigrations(): array
    {
        $qb = $this->connection->createQueryBuilder();
        $result = $qb->select('migration_class')
            ->from(self::MIGRATIONS_TABLE)
            ->orderBy('executed_at', 'ASC')
            ->executeQuery();

        /** @var list<string> */
        return $result->fetchFirstColumn();
    }

    private function executeMigration(string $migrationClass, string $direction): void
    {
        if (!class_exists($migrationClass)) {
            $file = $this->migrationsPath . '/' . $this->getFileNameFromClass($migrationClass);
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (!class_exists($migrationClass)) {
            throw new MigrationException(sprintf('Migration class "%s" not found.', $migrationClass));
        }

        /** @var MigrationInterface $migration */
        $migration = new $migrationClass();

        $schemaManager = $this->connection->createSchemaManager();
        
        if ($direction === 'up') {
            $schema = new \Doctrine\DBAL\Schema\Schema();
            $migration->up($schema);
            $sql = $schema->toSql($this->connection->getDatabasePlatform());
        } else {
            $schema = new \Doctrine\DBAL\Schema\Schema();
            $migration->down($schema);
            $sql = $schema->toSql($this->connection->getDatabasePlatform());
        }

        foreach ($sql as $query) {
            $this->connection->executeStatement($query);
        }
    }

    private function recordMigration(string $migrationClass): void
    {
        $this->connection->insert(
            self::MIGRATIONS_TABLE,
            [
                'migration_class' => $migrationClass,
                'executed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]
        );
    }

    private function removeMigration(string $migrationClass): void
    {
        $this->connection->delete(
            self::MIGRATIONS_TABLE,
            ['migration_class' => $migrationClass]
        );
    }

    private function ensureMigrationsTableExists(): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if ($schemaManager->tablesExist([self::MIGRATIONS_TABLE])) {
            return;
        }

        $schema = new Schema();
        $table = $schema->createTable(self::MIGRATIONS_TABLE);
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true, 'unsigned' => true]);
        $table->addColumn('migration_class', Types::STRING, ['length' => 255]);
        $table->addColumn('executed_at', Types::DATETIME_IMMUTABLE);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['migration_class']);

        $schemaManager->createTable($table);
    }
}

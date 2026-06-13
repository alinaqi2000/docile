<?php

declare(strict_types=1);

namespace Docile\Doctrine\Tests\Fixtures\Migrations;

use Docile\Doctrine\Migration\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

final class CreateUsersTable extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('users');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true, 'unsigned' => true]);
        $table->addColumn('name', Types::STRING, ['length' => 255]);
        $table->addColumn('email', Types::STRING, ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email']);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('users')) {
            $schema->dropTable('users');
        }
    }

    public function getDescription(): string
    {
        return 'Create users table';
    }
}

<?php

declare(strict_types=1);

namespace Docile\Doctrine\Migration;

use Doctrine\DBAL\Schema\Schema;

abstract class AbstractMigration implements MigrationInterface
{
    public function getDescription(): string
    {
        return '';
    }
}

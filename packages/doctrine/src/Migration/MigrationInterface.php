<?php

declare(strict_types=1);

namespace Docile\Doctrine\Migration;

use Doctrine\DBAL\Schema\Schema;

interface MigrationInterface
{
    public function up(Schema $schema): void;

    public function down(Schema $schema): void;

    public function getDescription(): string;
}

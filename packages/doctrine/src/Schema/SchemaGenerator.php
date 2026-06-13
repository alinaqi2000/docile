<?php

declare(strict_types=1);

namespace Docile\Doctrine\Schema;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

final class SchemaGenerator
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    /**
     * @param list<class-string> $entityClasses
     */
    public function generate(array $entityClasses): void
    {
        $metadata = [];
        foreach ($entityClasses as $class) {
            $metadata[] = $this->em->getClassMetadata($class);
        }

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->updateSchema($metadata);
    }

    /**
     * @param list<class-string> $entityClasses
     */
    public function drop(array $entityClasses): void
    {
        $metadata = [];
        foreach ($entityClasses as $class) {
            $metadata[] = $this->em->getClassMetadata($class);
        }

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropSchema($metadata);
    }
}

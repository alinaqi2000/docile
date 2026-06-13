<?php

declare(strict_types=1);

namespace Docile\Testing\Database;

trait RefreshDatabase
{
    private mixed $entityManager = null;

    protected function setUpRefreshDatabase(): void
    {
        if (isset($this->em) && is_object($this->em) && method_exists($this->em, 'beginTransaction')) {
            $this->entityManager = $this->em;
            $this->em->beginTransaction();
        }
    }

    protected function tearDownRefreshDatabase(): void
    {
        if ($this->entityManager !== null && is_object($this->entityManager) && method_exists($this->entityManager, 'getConnection')) {
            $connection = $this->entityManager->getConnection();
            if (is_object($connection) && method_exists($connection, 'isTransactionActive') && method_exists($this->entityManager, 'rollback')) {
                if ($connection->isTransactionActive()) {
                    $this->entityManager->rollback();
                }
            }
            $this->entityManager = null;
        }
    }
}

<?php

declare(strict_types=1);

namespace Docile\Testing\Tests\Database;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Docile\Testing\Database\RefreshDatabase::class)]
final class RefreshDatabaseTest extends TestCase
{
    use \Docile\Testing\Database\RefreshDatabase;

    public bool $beginTransactionCalled = false;
    public bool $rollbackCalled = false;
    public mixed $em = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beginTransactionCalled = false;
        $this->rollbackCalled = false;
        $this->em = null;
    }

    protected function tearDown(): void
    {
        $this->em = null;
        parent::tearDown();
    }

    public function testSetUpRefreshDatabaseWithoutEmDoesNothing(): void
    {
        $this->setUpRefreshDatabase();

        $this->assertFalse($this->beginTransactionCalled);
    }

    public function testTearDownRefreshDatabaseWithoutEmDoesNothing(): void
    {
        $this->tearDownRefreshDatabase();

        $this->assertFalse($this->rollbackCalled);
    }

    public function testSetUpRefreshDatabaseWithEmBeginsTransaction(): void
    {
        $this->em = $this->createMockEntityManager();
        $this->setUpRefreshDatabase();

        $this->assertTrue($this->beginTransactionCalled);
    }

    public function testTearDownRefreshDatabaseWithEmRollsBack(): void
    {
        $this->em = $this->createMockEntityManager();
        $this->setUpRefreshDatabase();
        $this->assertTrue($this->beginTransactionCalled);

        $this->tearDownRefreshDatabase();
    }

    public function testTearDownRefreshDatabaseWithInactiveTransactionDoesNotRollback(): void
    {
        $this->em = $this->createMockEntityManagerWithInactiveConnection();

        $this->setUpRefreshDatabase();
        $this->tearDownRefreshDatabase();

        $this->assertFalse($this->rollbackCalled);
    }

    private function createMockEntityManagerWithInactiveConnection(): object
    {
        $em = new class($this) {
            private bool $transactionActive = false;

            public function __construct(private readonly RefreshDatabaseTest $test)
            {
            }

            public function beginTransaction(): void
            {
                $this->test->beginTransactionCalled = true;
            }

            public function getConnection(): object
            {
                return new class($this->test, $this->transactionActive) {
                    public function __construct(
                        private readonly RefreshDatabaseTest $test,
                        private bool $transactionActive,
                    ) {}

                    public function isTransactionActive(): bool
                    {
                        return $this->transactionActive;
                    }

                    public function rollback(): void
                    {
                        $this->test->rollbackCalled = true;
                    }
                };
            }
        };

        return $em;
    }

    private function createMockEntityManager(): object
    {
        $em = new class($this) {
            private bool $transactionActive = true;

            public function __construct(private readonly RefreshDatabaseTest $test)
            {
            }

            public function beginTransaction(): void
            {
                $this->test->beginTransactionCalled = true;
            }

            public function getConnection(): object
            {
                return new class($this->test, $this->transactionActive) {
                    public function __construct(
                        private readonly RefreshDatabaseTest $test,
                        private bool $transactionActive,
                    ) {}

                    public function isTransactionActive(): bool
                    {
                        return $this->transactionActive;
                    }

                    public function rollback(): void
                    {
                        $this->test->rollbackCalled = true;
                    }
                };
            }
        };

        return $em;
    }
}

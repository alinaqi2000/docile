<?php

declare(strict_types=1);

namespace Docile\Doctrine\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Docile\Doctrine\Repository\DoctrineRepository;
use Docile\Doctrine\Tests\Fixtures\Entity\User;
use Docile\Doctrine\Tests\Fixtures\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

#[CoversClass(DoctrineRepository::class)]
final class DoctrineRepositoryTest extends TestCase
{
    private ?EntityManagerInterface $em;
    private UserRepository $repository;
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

        $this->repository = new UserRepository($this->em);

        $schemaTool = new SchemaTool($this->em);
        $metadata = [$this->em->getClassMetadata(User::class)];
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->em !== null) {
            $this->em->close();
            $this->em = null;
        }

        $files = glob($this->proxyDir . '/*.php');
        if ($files !== false) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public function testSavePersistsAndFlushesEntity(): void
    {
        $user = new User();
        $user->name = 'John Doe';
        $user->email = 'john@example.com';

        $this->repository->save($user);

        $this->assertNotNull($user->id);
    }

    public function testFindReturnsEntityById(): void
    {
        $user = new User();
        $user->name = 'Jane Doe';
        $user->email = 'jane@example.com';
        $this->repository->save($user);

        $found = $this->repository->find($user->id);

        $this->assertInstanceOf(User::class, $found);
        $this->assertSame($user->id, $found->id);
        $this->assertSame('Jane Doe', $found->name);
    }

    public function testFindReturnsNullForNonExistentId(): void
    {
        $found = $this->repository->find(999);

        $this->assertNull($found);
    }

    public function testFindAllReturnsAllEntities(): void
    {
        $user1 = new User();
        $user1->name = 'Alice';
        $user1->email = 'alice@example.com';
        $this->repository->save($user1);

        $user2 = new User();
        $user2->name = 'Bob';
        $user2->email = 'bob@example.com';
        $this->repository->save($user2);

        $all = $this->repository->findAll();

        $this->assertCount(2, $all);
        $this->assertContainsOnlyInstancesOf(User::class, $all);
    }

    public function testFindByReturnsMatchingEntities(): void
    {
        $user1 = new User();
        $user1->name = 'Charlie';
        $user1->email = 'charlie@example.com';
        $this->repository->save($user1);

        $user2 = new User();
        $user2->name = 'Charlie';
        $user2->email = 'charlie2@example.com';
        $this->repository->save($user2);

        $user3 = new User();
        $user3->name = 'David';
        $user3->email = 'david@example.com';
        $this->repository->save($user3);

        $results = $this->repository->findBy(['name' => 'Charlie']);

        $this->assertCount(2, $results);
    }

    public function testFindByWithOrderBy(): void
    {
        $user1 = new User();
        $user1->name = 'Zoe';
        $user1->email = 'zoe@example.com';
        $this->repository->save($user1);

        $user2 = new User();
        $user2->name = 'Adam';
        $user2->email = 'adam@example.com';
        $this->repository->save($user2);

        $results = $this->repository->findBy([], ['name' => 'ASC']);

        $this->assertCount(2, $results);
        $this->assertSame('Adam', $results[0]->name);
        $this->assertSame('Zoe', $results[1]->name);
    }

    public function testFindByWithLimit(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->name = "User{$i}";
            $user->email = "user{$i}@example.com";
            $this->repository->save($user);
        }

        $results = $this->repository->findBy([], null, 3);

        $this->assertCount(3, $results);
    }

    public function testFindByWithOffset(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->name = "User{$i}";
            $user->email = "user{$i}@example.com";
            $this->repository->save($user);
        }

        $results = $this->repository->findBy([], null, 2, 2);

        $this->assertCount(2, $results);
    }

    public function testFindOneByReturnsSingleMatchingEntity(): void
    {
        $user = new User();
        $user->name = 'Eve';
        $user->email = 'eve@example.com';
        $this->repository->save($user);

        $found = $this->repository->findOneBy(['email' => 'eve@example.com']);

        $this->assertInstanceOf(User::class, $found);
        $this->assertSame('Eve', $found->name);
    }

    public function testFindOneByReturnsNullWhenNoMatch(): void
    {
        $found = $this->repository->findOneBy(['email' => 'nonexistent@example.com']);

        $this->assertNull($found);
    }

    public function testRemoveDeletesEntity(): void
    {
        $user = new User();
        $user->name = 'Frank';
        $user->email = 'frank@example.com';
        $this->repository->save($user);

        $id = $user->id;
        $this->repository->remove($user);

        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testCountReturnsNumberOfEntities(): void
    {
        $this->assertSame(0, $this->repository->count());

        for ($i = 1; $i <= 3; $i++) {
            $user = new User();
            $user->name = "User{$i}";
            $user->email = "user{$i}@example.com";
            $this->repository->save($user);
        }

        $this->assertSame(3, $this->repository->count());
    }

    public function testCountWithCriteria(): void
    {
        $user1 = new User();
        $user1->name = 'Grace';
        $user1->email = 'grace@example.com';
        $this->repository->save($user1);

        $user2 = new User();
        $user2->name = 'Grace';
        $user2->email = 'grace2@example.com';
        $this->repository->save($user2);

        $user3 = new User();
        $user3->name = 'Henry';
        $user3->email = 'henry@example.com';
        $this->repository->save($user3);

        $count = $this->repository->count(['name' => 'Grace']);

        $this->assertSame(2, $count);
    }

    public function testCreateQueryBuilderReturnsQueryBuilder(): void
    {
        $qb = $this->repository->createQueryBuilder('u');

        $this->assertInstanceOf(\Doctrine\ORM\QueryBuilder::class, $qb);
    }
}

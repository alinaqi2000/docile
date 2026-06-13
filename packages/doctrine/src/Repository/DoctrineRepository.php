<?php

declare(strict_types=1);

namespace Docile\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Docile\Doctrine\Exception\EntityNotFoundException;

/**
 * @template T of object
 * @template-extends EntityRepository<T>
 * @implements RepositoryInterface<T>
 */
abstract class DoctrineRepository extends EntityRepository implements RepositoryInterface
{
    /**
     * @param EntityManagerInterface $em
     * @param class-string<T> $entityClass
     */
    public function __construct(EntityManagerInterface $em, string $entityClass)
    {
        $metadata = $em->getClassMetadata($entityClass);
        parent::__construct($em, $metadata);
    }

    /**
     * @param mixed $id
     * @param \Doctrine\DBAL\LockMode|int|null $lockMode
     * @param int|null $lockVersion
     * @return T|null
     */
    public function find(mixed $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    /**
     * @return list<T>
     */
    public function findAll(): array
    {
        /** @var list<T> */
        return parent::findAll();
    }

    /**
     * @param array<string,mixed> $criteria
     * @param array<string,'ASC'|'DESC'|'asc'|'desc'>|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return list<T>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        /** @var list<T> */
        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @param array<string,mixed> $criteria
     * @param array<string,string>|null $orderBy
     * @return T|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        return parent::findOneBy($criteria, $orderBy);
    }

    /**
     * @param T $entity
     */
    public function save(object $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * @param T $entity
     */
    public function remove(object $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * @param array<string,mixed> $criteria
     */
    public function count(array $criteria = []): int
    {
        return parent::count($criteria);
    }

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder(string $alias, ?string $indexBy = null): QueryBuilder
    {
        return parent::createQueryBuilder($alias, $indexBy);
    }

    /**
     * @return EntityManagerInterface
     */
    protected function em(): EntityManagerInterface
    {
        return $this->getEntityManager();
    }
}

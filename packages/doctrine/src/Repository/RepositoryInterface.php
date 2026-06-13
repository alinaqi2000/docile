<?php

declare(strict_types=1);

namespace Docile\Doctrine\Repository;

/**
 * @template T of object
 */
interface RepositoryInterface
{
    /**
     * @param mixed $id
     * @param \Doctrine\DBAL\LockMode|int|null $lockMode
     * @param int|null $lockVersion
     * @return T|null
     */
    public function find(mixed $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object;

    /**
     * @return list<T>
     */
    public function findAll(): array;

    /**
     * @param array<string,mixed> $criteria
     * @param array<string,'ASC'|'DESC'|'asc'|'desc'>|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return list<T>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * @param array<string,mixed> $criteria
     * @param array<string,string>|null $orderBy
     * @return T|null
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /**
     * @param T $entity
     */
    public function save(object $entity): void;

    /**
     * @param T $entity
     */
    public function remove(object $entity): void;

    /**
     * @param array<string,mixed> $criteria
     */
    public function count(array $criteria = []): int;
}

<?php

declare(strict_types=1);

namespace Docile\Doctrine\Tests\Fixtures\Repository;

use Docile\Doctrine\Repository\DoctrineRepository;
use Docile\Doctrine\Tests\Fixtures\Entity\User;

final class UserRepository extends DoctrineRepository
{
    public function __construct(\Doctrine\ORM\EntityManagerInterface $em)
    {
        parent::__construct($em, User::class);
    }
}

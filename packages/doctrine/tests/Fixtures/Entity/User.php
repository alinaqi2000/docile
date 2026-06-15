<?php

declare(strict_types=1);

namespace Docile\Doctrine\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\Column(type: 'string')]
    public string $name;

    #[ORM\Column(type: 'string', unique: true)]
    public string $email;
}

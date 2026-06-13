<?php

declare(strict_types=1);

namespace Docile\Security\Authz;

use Docile\Security\Auth\UserInterface;

abstract class Policy
{
    /** User-defined policies extend this and implement ability methods. */
    abstract public function check(UserInterface|null $user, mixed ...$args): bool;
}

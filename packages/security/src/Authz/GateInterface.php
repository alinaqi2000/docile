<?php

declare(strict_types=1);

namespace Docile\Security\Authz;

use Docile\Security\Auth\UserInterface;

interface GateInterface
{
    /** Define an ability with a callback. */
    /** @param callable(UserInterface|null, mixed...): bool $callback */
    public function define(string $ability, callable $callback): void;

    /** Check if the ability is allowed. */
    public function allows(string $ability, mixed ...$args): bool;

    /** Check if the ability is denied. */
    public function denies(string $ability, mixed ...$args): bool;
}

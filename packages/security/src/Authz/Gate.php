<?php

declare(strict_types=1);

namespace Docile\Security\Authz;

use Docile\Security\Auth\UserInterface;

final class Gate implements GateInterface
{
    /** @var array<string, callable(UserInterface|null, mixed...): bool> */
    private array $abilities = [];

    /** @var array<string, string> */
    private array $policies = [];

    #[\Override]
    /** @param callable(UserInterface|null, mixed...): bool $callback */
    public function define(string $ability, callable $callback): void
    {
        $this->abilities[$ability] = $callback;
    }

    /** Register a policy class for a model. */
    public function registerPolicy(string $model, string $policyClass): void
    {
        $this->policies[$model] = $policyClass;
    }

    #[\Override]
    public function allows(string $ability, mixed ...$args): bool
    {
        $user = array_shift($args);

        if ($user !== null && !$user instanceof UserInterface) {
            return false;
        }

        if (isset($this->abilities[$ability])) {
            return (bool) ($this->abilities[$ability])($user, ...$args);
        }

        if (count($args) > 0) {
            $model = $args[0];

            if (is_object($model)) {
                $modelClass = $model::class;

                if (isset($this->policies[$modelClass])) {
                    $policyClass = $this->policies[$modelClass];

                    if (class_exists($policyClass) && method_exists($policyClass, $ability)) {
                        $policy = new $policyClass();

                        return (bool) $policy->$ability($user, ...$args); // @phpstan-ignore-line
                    }
                }
            }
        }

        return false;
    }

    #[\Override]
    public function denies(string $ability, mixed ...$args): bool
    {
        return !$this->allows($ability, ...$args);
    }
}

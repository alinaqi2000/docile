<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Authz;

use Docile\Security\Auth\UserInterface;
use Docile\Security\Authz\Gate;
use Docile\Security\Authz\GateInterface;
use Docile\Security\Tests\Fixtures\TestUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Gate::class)]
final class GateTest extends TestCase
{
    private GateInterface $gate;
    private TestUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gate = new Gate();
        $this->user = new TestUser(1, 'user@example.com', 'hash');
    }

    public function testAllowsReturnsTrueWhenAbilityReturnsTrue(): void
    {
        $this->gate->define('edit-post', fn () => true);

        $this->assertTrue($this->gate->allows('edit-post'));
    }

    public function testAllowsReturnsFalseWhenAbilityReturnsFalse(): void
    {
        $this->gate->define('edit-post', fn () => false);

        $this->assertFalse($this->gate->allows('edit-post'));
    }

    public function testAllowsPassesUserToCallback(): void
    {
        $this->gate->define('edit-post', function (UserInterface|null $user) {
            return $user !== null && $user->getId() === 1;
        });

        $this->assertTrue($this->gate->allows('edit-post', $this->user));
    }

    public function testAllowsReturnsFalseForUndefinedAbility(): void
    {
        $this->assertFalse($this->gate->allows('undefined-ability'));
    }

    public function testAllowsPassesAdditionalArguments(): void
    {
        $this->gate->define('edit-post', function (UserInterface|null $user, int $postId) {
            return $postId === 123;
        });

        $this->assertTrue($this->gate->allows('edit-post', $this->user, 123));
        $this->assertFalse($this->gate->allows('edit-post', $this->user, 456));
    }

    public function testDeniesReturnsOppositeOfAllows(): void
    {
        $this->gate->define('edit-post', fn () => true);

        $this->assertFalse($this->gate->denies('edit-post'));
    }

    public function testDeniesReturnsTrueWhenAllowsReturnsFalse(): void
    {
        $this->gate->define('edit-post', fn () => false);

        $this->assertTrue($this->gate->denies('edit-post'));
    }

    public function testAllowsReturnsFalseWhenUserIsNotUserInterface(): void
    {
        $this->gate->define('edit-post', fn () => true);

        $this->assertFalse($this->gate->allows('edit-post', 'not-a-user'));
    }

    public function testRegisterPolicyStoresPolicyForModel(): void
    {
        $this->gate->registerPolicy(TestUser::class, TestPolicy::class);

        $result = $this->gate->allows('editPost', $this->user, $this->user);

        $this->assertTrue($result);
    }

    public function testPolicyMethodIsCalledWhenRegistered(): void
    {
        $this->gate->registerPolicy(TestUser::class, TestPolicy::class);

        $result = $this->gate->allows('customAbility', $this->user, $this->user);

        $this->assertTrue($result);
    }

    public function testPolicyMethodReceivesUserAndArguments(): void
    {
        $this->gate->registerPolicy(TestUser::class, TestPolicy::class);

        $result = $this->gate->allows('checkUser', $this->user, $this->user);

        $this->assertTrue($result);
    }

    public function testAllowsReturnsFalseWhenPolicyMethodDoesNotExist(): void
    {
        $this->gate->registerPolicy(TestUser::class, TestPolicy::class);

        $result = $this->gate->allows('nonExistentMethod', $this->user, $this->user);

        $this->assertFalse($result);
    }

    public function testAllowsReturnsFalseWhenPolicyNotRegisteredForModel(): void
    {
        $result = $this->gate->allows('someAbility', $this->user, $this->user);

        $this->assertFalse($result);
    }

    public function testMultipleAbilitiesCanBeDefined(): void
    {
        $this->gate->define('ability1', fn () => true);
        $this->gate->define('ability2', fn () => false);

        $this->assertTrue($this->gate->allows('ability1'));
        $this->assertFalse($this->gate->allows('ability2'));
    }

    public function testAbilityCanBeOverridden(): void
    {
        $this->gate->define('ability', fn () => false);
        $this->gate->define('ability', fn () => true);

        $this->assertTrue($this->gate->allows('ability'));
    }
}

final class TestPolicy
{
    public function editPost(UserInterface|null $user, mixed $args): bool
    {
        return true;
    }

    public function customAbility(UserInterface|null $user, mixed $args): bool
    {
        return true;
    }

    public function checkUser(UserInterface|null $user, TestUser $target): bool
    {
        return $user !== null && $user->getId() === $target->getId();
    }
}

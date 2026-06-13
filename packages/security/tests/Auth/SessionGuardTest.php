<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Auth;

use Docile\Security\Auth\SessionGuard;
use Docile\Security\Password\SodiumHasher;
use Docile\Security\Tests\Fixtures\TestUser;
use Docile\Security\Tests\Fixtures\TestUserProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SessionGuard::class)]
final class SessionGuardTest extends TestCase
{
    private SessionGuard $guard;
    private TestUserProvider $provider;
    private SodiumHasher $hasher;
    /** @var array<string, mixed> */
    private array $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new TestUserProvider();
        $this->hasher = new SodiumHasher();
        $this->guard = new SessionGuard($this->provider, $this->hasher);
        $this->session = [];

        $user = new TestUser(1, 'user@example.com', $this->hasher->hash('password'));
        $this->provider->addUser($user);
    }

    public function testAttemptReturnsTrueForValidCredentials(): void
    {
        $result = $this->guard->attempt(['email' => 'user@example.com', 'password' => 'password'], $this->session);

        $this->assertTrue($result);
        $this->assertArrayHasKey('_auth_user_id', $this->session);
        $this->assertSame(1, $this->session['_auth_user_id']);
    }

    public function testAttemptReturnsFalseForInvalidEmail(): void
    {
        $result = $this->guard->attempt(['email' => 'wrong@example.com', 'password' => 'password'], $this->session);

        $this->assertFalse($result);
        $this->assertArrayNotHasKey('_auth_user_id', $this->session);
    }

    public function testAttemptReturnsFalseForInvalidPassword(): void
    {
        $result = $this->guard->attempt(['email' => 'user@example.com', 'password' => 'wrong'], $this->session);

        $this->assertFalse($result);
        $this->assertArrayNotHasKey('_auth_user_id', $this->session);
    }

    public function testAttemptReturnsFalseWhenPasswordNotProvided(): void
    {
        $result = $this->guard->attempt(['email' => 'user@example.com'], $this->session);

        $this->assertFalse($result);
    }

    public function testUserReturnsAuthenticatedUser(): void
    {
        $this->guard->attempt(['email' => 'user@example.com', 'password' => 'password'], $this->session);

        $user = $this->guard->user($this->session);

        $this->assertNotNull($user);
        $this->assertSame(1, $user->getId());
        $this->assertSame('user@example.com', $user->getAuthIdentifier());
    }

    public function testUserReturnsNullWhenNotAuthenticated(): void
    {
        $user = $this->guard->user($this->session);

        $this->assertNull($user);
    }

    public function testUserReturnsNullWhenSessionHasInvalidId(): void
    {
        $this->session['_auth_user_id'] = 999;

        $user = $this->guard->user($this->session);

        $this->assertNull($user);
    }

    public function testLogoutClearsSession(): void
    {
        $this->guard->attempt(['email' => 'user@example.com', 'password' => 'password'], $this->session);

        $this->assertArrayHasKey('_auth_user_id', $this->session);

        $this->guard->logout($this->session);

        $this->assertArrayNotHasKey('_auth_user_id', $this->session);
    }

    public function testLogoutDoesNothingWhenNotAuthenticated(): void
    {
        $this->session['other_key'] = 'value';

        $this->guard->logout($this->session);

        $this->assertArrayNotHasKey('_auth_user_id', $this->session);
        $this->assertArrayHasKey('other_key', $this->session);
    }

    public function testMultipleUsersCanBeAuthenticated(): void
    {
        $user2 = new TestUser(2, 'user2@example.com', $this->hasher->hash('password2'));
        $this->provider->addUser($user2);

        $session1 = [];
        $session2 = [];

        $this->guard->attempt(['email' => 'user@example.com', 'password' => 'password'], $session1);
        $this->guard->attempt(['email' => 'user2@example.com', 'password' => 'password2'], $session2);

        $user1 = $this->guard->user($session1);
        $user2Result = $this->guard->user($session2);

        $this->assertSame(1, $user1->getId());
        $this->assertSame(2, $user2Result->getId());
    }
}

<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Auth;

use Docile\Security\Auth\ApiKeyGuard;
use Docile\Security\Exception\AuthenticationException;
use Docile\Security\Tests\Fixtures\TestUser;
use Docile\Security\Tests\Fixtures\TestUserProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiKeyGuard::class)]
final class ApiKeyGuardTest extends TestCase
{
    private ApiKeyGuard $guard;
    private TestUserProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new TestUserProvider();
        $this->guard = new ApiKeyGuard($this->provider);

        $user = new TestUser(1, 'user@example.com', 'hash', 'valid-api-key');
        $this->provider->addUser($user);
    }

    public function testAuthenticateReturnsUserForValidApiKey(): void
    {
        $headers = ['X-API-Key' => 'valid-api-key'];

        $user = $this->guard->authenticate($headers);

        $this->assertNotNull($user);
        $this->assertSame(1, $user->getId());
        $this->assertSame('user@example.com', $user->getAuthIdentifier());
    }

    public function testAuthenticateReturnsNullWhenHeaderMissing(): void
    {
        $headers = [];

        $user = $this->guard->authenticate($headers);

        $this->assertNull($user);
    }

    public function testAuthenticateReturnsNullWhenHeaderEmpty(): void
    {
        $headers = ['X-API-Key' => ''];

        $user = $this->guard->authenticate($headers);

        $this->assertNull($user);
    }

    public function testAuthenticateThrowsExceptionForInvalidApiKey(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid API key.');

        $this->guard->authenticateByKey('invalid-api-key');
    }

    public function testAuthenticateByKeyReturnsUserForValidApiKey(): void
    {
        $user = $this->guard->authenticateByKey('valid-api-key');

        $this->assertSame(1, $user->getId());
    }

    public function testAuthenticateIsCaseSensitive(): void
    {
        $headers = ['X-API-Key' => 'Valid-API-Key'];

        $user = $this->guard->authenticate($headers);

        $this->assertNull($user);
    }

    public function testAuthenticateIgnoresOtherHeaders(): void
    {
        $headers = [
            'Authorization' => 'Bearer token',
            'Content-Type' => 'application/json',
        ];

        $user = $this->guard->authenticate($headers);

        $this->assertNull($user);
    }

    public function testAuthenticateWithMultipleUsers(): void
    {
        $user2 = new TestUser(2, 'user2@example.com', 'hash2', 'another-api-key');
        $this->provider->addUser($user2);

        $headers1 = ['X-API-Key' => 'valid-api-key'];
        $headers2 = ['X-API-Key' => 'another-api-key'];

        $user1 = $this->guard->authenticate($headers1);
        $user2Result = $this->guard->authenticate($headers2);

        $this->assertSame(1, $user1->getId());
        $this->assertSame(2, $user2Result->getId());
    }
}

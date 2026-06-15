<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Auth\Token;

use Docile\Security\Auth\Token\JwtCodec;
use Docile\Security\Auth\Token\TokenGuard;
use Docile\Security\Exception\InvalidTokenException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[CoversClass(TokenGuard::class)]
final class TokenGuardTest extends TestCase
{
    private TokenGuard $guard;
    private JwtCodec $codec;
    private string $secret;
    private ClockInterface $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('@' . time()));
        $this->codec = new JwtCodec($this->clock);
        $this->guard = new TokenGuard($this->codec);
        $this->secret = 'test-secret-key';
    }

    public function testAuthenticateReturnsClaimsForValidToken(): void
    {
        $claims = ['sub' => '123', 'name' => 'John'];
        $token = $this->codec->encode($claims, $this->secret);
        $bearerToken = 'Bearer ' . $token;

        $result = $this->guard->authenticate($bearerToken, $this->secret);

        $this->assertSame('123', $result['sub']);
        $this->assertSame('John', $result['name']);
    }

    public function testAuthenticateThrowsExceptionForMissingBearerPrefix(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid bearer token format.');

        $this->guard->authenticate('invalid-token', $this->secret);
    }

    public function testAuthenticateThrowsExceptionForInvalidToken(): void
    {
        $this->expectException(InvalidTokenException::class);

        $this->guard->authenticate('Bearer invalid-token', $this->secret);
    }

    public function testAuthenticateThrowsExceptionForExpiredToken(): void
    {
        $claims = ['sub' => '123'];
        $token = $this->codec->encode($claims, $this->secret, -1);
        $bearerToken = 'Bearer ' . $token;

        $this->expectException(InvalidTokenException::class);

        $this->guard->authenticate($bearerToken, $this->secret);
    }

    public function testAuthenticateThrowsExceptionForWrongSecret(): void
    {
        $claims = ['sub' => '123'];
        $token = $this->codec->encode($claims, $this->secret);
        $bearerToken = 'Bearer ' . $token;

        $this->expectException(InvalidTokenException::class);

        $this->guard->authenticate($bearerToken, 'wrong-secret');
    }

    public function testAuthenticateReturnsAllClaimsIncludingIatAndExp(): void
    {
        $claims = ['sub' => '123'];
        $token = $this->codec->encode($claims, $this->secret, 3600);
        $bearerToken = 'Bearer ' . $token;

        $result = $this->guard->authenticate($bearerToken, $this->secret);

        $this->assertArrayHasKey('iat', $result);
        $this->assertArrayHasKey('exp', $result);
        $this->assertArrayHasKey('sub', $result);
    }
}

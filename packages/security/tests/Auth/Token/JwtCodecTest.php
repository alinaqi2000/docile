<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Auth\Token;

use Docile\Security\Auth\Token\JwtCodec;
use Docile\Security\Exception\InvalidTokenException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JwtCodec::class)]
final class JwtCodecTest extends TestCase
{
    private JwtCodec $codec;
    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new JwtCodec();
        $this->secret = 'test-secret-key';
    }

    public function testEncodeReturnsValidToken(): void
    {
        $claims = ['sub' => '123', 'name' => 'John'];
        $token = $this->codec->encode($claims, $this->secret);

        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);
    }

    public function testEncodeAddsIatAndExpClaims(): void
    {
        $claims = ['sub' => '123'];
        $token = $this->codec->encode($claims, $this->secret, 3600);

        $decoded = $this->codec->decode($token, $this->secret);

        $this->assertArrayHasKey('iat', $decoded);
        $this->assertArrayHasKey('exp', $decoded);
        $this->assertArrayHasKey('sub', $decoded);
        $this->assertSame('123', $decoded['sub']);
    }

    public function testDecodeReturnsClaims(): void
    {
        $claims = ['sub' => '123', 'name' => 'John'];
        $token = $this->codec->encode($claims, $this->secret);

        $decoded = $this->codec->decode($token, $this->secret);

        $this->assertSame('123', $decoded['sub']);
        $this->assertSame('John', $decoded['name']);
    }

    public function testDecodeThrowsExceptionForInvalidSignature(): void
    {
        $claims = ['sub' => '123'];
        $token = $this->codec->encode($claims, $this->secret);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid token signature.');

        $this->codec->decode($token, 'wrong-secret');
    }

    public function testDecodeThrowsExceptionForExpiredToken(): void
    {
        $claims = ['sub' => '123'];
        $token = $this->codec->encode($claims, $this->secret, -1);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Token has expired.');

        $this->codec->decode($token, $this->secret);
    }

    public function testDecodeThrowsExceptionForInvalidFormat(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid token format.');

        $this->codec->decode('invalid-token', $this->secret);
    }

    public function testDecodeThrowsExceptionForMissingParts(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid token format.');

        $this->codec->decode('header.payload', $this->secret);
    }

    public function testDecodeThrowsExceptionForMissingExpiration(): void
    {
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = $this->base64UrlEncode(json_encode(['sub' => '123']));
        $signature = hash_hmac('sha256', $header . '.' . $payload, $this->secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        $token = $header . '.' . $payload . '.' . $signatureEncoded;

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Token missing expiration.');

        $this->codec->decode($token, $this->secret);
    }

    public function testEncodeWithCustomTtl(): void
    {
        $claims = ['sub' => '123'];
        $token = $this->codec->encode($claims, $this->secret, 7200);

        $decoded = $this->codec->decode($token, $this->secret);

        $this->assertGreaterThan(time() + 3600, $decoded['exp']);
    }

    public function testEncodePreservesOriginalClaims(): void
    {
        $claims = ['sub' => '123', 'name' => 'John', 'role' => 'admin'];
        $token = $this->codec->encode($claims, $this->secret);

        $decoded = $this->codec->decode($token, $this->secret);

        $this->assertSame('123', $decoded['sub']);
        $this->assertSame('John', $decoded['name']);
        $this->assertSame('admin', $decoded['role']);
    }

    public function testDecodeHandlesSpecialCharactersInClaims(): void
    {
        $claims = ['sub' => '123', 'name' => 'John "The Boss"'];
        $token = $this->codec->encode($claims, $this->secret);

        $decoded = $this->codec->decode($token, $this->secret);

        $this->assertSame('John "The Boss"', $decoded['name']);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

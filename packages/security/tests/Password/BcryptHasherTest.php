<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Password;

use Docile\Security\Password\BcryptHasher;
use Docile\Security\Password\HasherInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BcryptHasher::class)]
final class BcryptHasherTest extends TestCase
{
    private HasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new BcryptHasher();
    }

    public function testHashReturnsString(): void
    {
        $hash = $this->hasher->hash('password');

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
        $this->assertStringStartsWith('$2y$', $hash);
    }

    public function testHashGeneratesDifferentHashesForSamePassword(): void
    {
        $hash1 = $this->hasher->hash('password');
        $hash2 = $this->hasher->hash('password');

        $this->assertNotSame($hash1, $hash2);
    }

    public function testVerifyReturnsTrueForCorrectPassword(): void
    {
        $plain = 'my-secure-password';
        $hash = $this->hasher->hash($plain);

        $this->assertTrue($this->hasher->verify($plain, $hash));
    }

    public function testVerifyReturnsFalseForIncorrectPassword(): void
    {
        $hash = $this->hasher->hash('correct-password');

        $this->assertFalse($this->hasher->verify('wrong-password', $hash));
    }

    public function testVerifyReturnsFalseForInvalidHash(): void
    {
        $this->assertFalse($this->hasher->verify('password', 'invalid-hash'));
    }

    public function testNeedsRehashReturnsFalseForFreshHash(): void
    {
        $hash = $this->hasher->hash('password');

        $this->assertFalse($this->hasher->needsRehash($hash));
    }

    public function testNeedsRehashReturnsTrueForDifferentCost(): void
    {
        $hasherLowCost = new BcryptHasher(4);
        $hasherHighCost = new BcryptHasher(12);

        $hash = $hasherLowCost->hash('password');

        $this->assertTrue($hasherHighCost->needsRehash($hash));
    }

    public function testConstructorWithCustomCost(): void
    {
        $hasher = new BcryptHasher(10);
        $hash = $hasher->hash('password');

        $this->assertStringContainsString('$2y$10$', $hash);
    }

    public function testConstructorThrowsExceptionForCostBelowMinimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BcryptHasher(3);
    }

    public function testConstructorThrowsExceptionForCostAboveMaximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BcryptHasher(32);
    }

    public function testHashHandlesEmptyString(): void
    {
        $hash = $this->hasher->hash('');

        $this->assertIsString($hash);
        $this->assertTrue($this->hasher->verify('', $hash));
    }

    public function testHashHandlesLongPassword(): void
    {
        $longPassword = str_repeat('a', 72);
        $hash = $this->hasher->hash($longPassword);

        $this->assertTrue($this->hasher->verify($longPassword, $hash));
    }
}

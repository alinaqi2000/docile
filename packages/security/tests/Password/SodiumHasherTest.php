<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Password;

use Docile\Security\Password\HasherInterface;
use Docile\Security\Password\SodiumHasher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SodiumHasher::class)]
final class SodiumHasherTest extends TestCase
{
    private HasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new SodiumHasher();
    }

    public function testHashReturnsString(): void
    {
        $hash = $this->hasher->hash('password');

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
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

    public function testNeedsRehashReturnsTrueForOutdatedHash(): void
    {
        $oldHash = '$argon2id$v=19$m=65536,t=4,p=1$' . str_repeat('a', 16) . '$' . str_repeat('b', 32);

        $this->assertTrue($this->hasher->needsRehash($oldHash));
    }

    public function testHashHandlesEmptyString(): void
    {
        $hash = $this->hasher->hash('');

        $this->assertIsString($hash);
        $this->assertTrue($this->hasher->verify('', $hash));
    }

    public function testHashHandlesLongPassword(): void
    {
        $longPassword = str_repeat('a', 1000);
        $hash = $this->hasher->hash($longPassword);

        $this->assertTrue($this->hasher->verify($longPassword, $hash));
    }
}

<?php

declare(strict_types=1);

namespace Docile\Security\Tests\Csrf;

use Docile\Security\Csrf\CsrfTokenManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CsrfTokenManager::class)]
final class CsrfTokenManagerTest extends TestCase
{
    private CsrfTokenManager $manager;
    /** @var array<string, mixed> */
    private array $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new CsrfTokenManager();
        $this->session = [];
    }

    public function testGenerateReturnsToken(): void
    {
        $token = $this->manager->generate($this->session);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testGenerateStoresTokenInSession(): void
    {
        $token = $this->manager->generate($this->session);

        $this->assertArrayHasKey('_csrf_token', $this->session);
        $this->assertSame($token, $this->session['_csrf_token']);
    }

    public function testGenerateCreatesNewTokenEachCall(): void
    {
        $token1 = $this->manager->generate($this->session);
        $token2 = $this->manager->generate($this->session);

        $this->assertNotSame($token1, $token2);
    }

    public function testValidateReturnsTrueForMatchingToken(): void
    {
        $token = $this->manager->generate($this->session);

        $this->assertTrue($this->manager->validate($token, $this->session));
    }

    public function testValidateReturnsFalseForNonMatchingToken(): void
    {
        $this->manager->generate($this->session);

        $this->assertFalse($this->manager->validate('wrong-token', $this->session));
    }

    public function testValidateReturnsFalseWhenNoTokenInSession(): void
    {
        $this->assertFalse($this->manager->validate('some-token', $this->session));
    }

    public function testValidateReturnsFalseForEmptyToken(): void
    {
        $this->manager->generate($this->session);

        $this->assertFalse($this->manager->validate('', $this->session));
    }

    public function testValidateUsesTimingSafeComparison(): void
    {
        $token = $this->manager->generate($this->session);

        $this->assertTrue($this->manager->validate($token, $this->session));
    }

    public function testGenerateCreates64CharacterToken(): void
    {
        $token = $this->manager->generate($this->session);

        $this->assertSame(64, strlen($token));
    }

    public function testValidateAfterRegenerate(): void
    {
        $token1 = $this->manager->generate($this->session);
        $token2 = $this->manager->generate($this->session);

        $this->assertFalse($this->manager->validate($token1, $this->session));
        $this->assertTrue($this->manager->validate($token2, $this->session));
    }

    public function testSessionCanContainOtherData(): void
    {
        $this->session['other_key'] = 'other_value';

        $token = $this->manager->generate($this->session);

        $this->assertArrayHasKey('other_key', $this->session);
        $this->assertSame('other_value', $this->session['other_key']);
        $this->assertTrue($this->manager->validate($token, $this->session));
    }
}

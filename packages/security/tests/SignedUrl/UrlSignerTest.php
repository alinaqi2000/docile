<?php

declare(strict_types=1);

namespace Docile\Security\Tests\SignedUrl;

use Docile\Security\SignedUrl\UrlSigner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[CoversClass(UrlSigner::class)]
final class UrlSignerTest extends TestCase
{
    private UrlSigner $signer;
    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();

        $clock = $this->createMock(\Psr\Clock\ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('@' . time()));
        $this->signer = new UrlSigner($clock);
        $this->secret = 'test-secret-key';
    }

    public function testSignReturnsUrlWithSignatureAndExpires(): void
    {
        $url = 'https://example.com/path';
        $signed = $this->signer->sign($url, $this->secret);

        $this->assertStringContainsString('signature=', $signed);
        $this->assertStringContainsString('expires=', $signed);
    }

    public function testSignAppendsToUrlWithoutQuery(): void
    {
        $url = 'https://example.com/path';
        $signed = $this->signer->sign($url, $this->secret);

        $this->assertStringStartsWith($url . '?', $signed);
    }

    public function testSignAppendsToUrlWithExistingQuery(): void
    {
        $url = 'https://example.com/path?foo=bar';
        $signed = $this->signer->sign($url, $this->secret);

        $this->assertStringStartsWith($url . '&', $signed);
    }

    public function testValidateReturnsTrueForValidSignedUrl(): void
    {
        $url = 'https://example.com/path';
        $signed = $this->signer->sign($url, $this->secret);

        $this->assertTrue($this->signer->validate($signed, $this->secret));
    }

    public function testValidateReturnsFalseForInvalidSignature(): void
    {
        $url = 'https://example.com/path';
        $signed = $this->signer->sign($url, $this->secret);

        $this->assertFalse($this->signer->validate($signed, 'wrong-secret'));
    }

    public function testValidateReturnsFalseForExpiredUrl(): void
    {
        $url = 'https://example.com/path';
        $signed = $this->signer->sign($url, $this->secret, -1);

        $this->assertFalse($this->signer->validate($signed, $this->secret));
    }

    public function testValidateReturnsFalseForUrlWithoutSignature(): void
    {
        $url = 'https://example.com/path';

        $this->assertFalse($this->signer->validate($url, $this->secret));
    }

    public function testValidateReturnsFalseForUrlWithoutExpires(): void
    {
        $url = 'https://example.com/path?signature=abc123';

        $this->assertFalse($this->signer->validate($url, $this->secret));
    }

    public function testValidateReturnsFalseForInvalidUrl(): void
    {
        $url = 'not-a-valid-url';

        $this->assertFalse($this->signer->validate($url, $this->secret));
    }

    public function testSignWithCustomTtl(): void
    {
        $url = 'https://example.com/path';
        $signed = $this->signer->sign($url, $this->secret, 7200);

        $this->assertStringContainsString('signature=', $signed);
        $this->assertStringContainsString('expires=', $signed);
        $this->assertTrue($this->signer->validate($signed, $this->secret));
    }

    public function testSignPreservesExistingQueryParameters(): void
    {
        $url = 'https://example.com/path?foo=bar&baz=qux';
        $signed = $this->signer->sign($url, $this->secret);

        $this->assertStringContainsString('foo=bar', $signed);
        $this->assertStringContainsString('baz=qux', $signed);
        $this->assertTrue($this->signer->validate($signed, $this->secret));
    }

    public function testValidateHandlesUrlWithFragment(): void
    {
        $url = 'https://example.com/path#section';
        $signed = $this->signer->sign($url, $this->secret);

        $this->assertStringContainsString('#section', $signed);
        $this->assertTrue($this->signer->validate($signed, $this->secret));
    }

    public function testValidateHandlesUrlWithPort(): void
    {
        $url = 'https://example.com:8080/path';
        $signed = $this->signer->sign($url, $this->secret);

        $this->assertStringContainsString(':8080', $signed);
        $this->assertTrue($this->signer->validate($signed, $this->secret));
    }

    public function testValidateHandlesUrlWithPathWithSpecialCharacters(): void
    {
        $url = 'https://example.com/path/with spaces/and-dashes';
        $signed = $this->signer->sign($url, $this->secret);

        $this->assertTrue($this->signer->validate($signed, $this->secret));
    }

    public function testSignGeneratesDifferentSignaturesForSameUrl(): void
    {
        $url = 'https://example.com/path';

        $clock1 = $this->createMock(ClockInterface::class);
        $clock1->method('now')->willReturn(new \DateTimeImmutable('@' . time()));
        $signer1 = new UrlSigner($clock1);
        $signed1 = $signer1->sign($url, $this->secret);

        $clock2 = $this->createMock(ClockInterface::class);
        $clock2->method('now')->willReturn(new \DateTimeImmutable('@' . (time() + 1)));
        $signer2 = new UrlSigner($clock2);
        $signed2 = $signer2->sign($url, $this->secret);

        $this->assertNotSame($signed1, $signed2);
    }

    public function testValidateRemovesSignatureAndExpiresBeforeChecking(): void
    {
        $url = 'https://example.com/path?foo=bar';
        $signed = $this->signer->sign($url, $this->secret);

        $this->assertTrue($this->signer->validate($signed, $this->secret));
    }

    public function testSignWithDefaultTtl(): void
    {
        $url = 'https://example.com/path';
        $signed = $this->signer->sign($url, $this->secret);

        $this->assertTrue($this->signer->validate($signed, $this->secret));
    }

    public function testValidateReturnsFalseForTamperedSignature(): void
    {
        $url = 'https://example.com/path';
        $signed = $this->signer->sign($url, $this->secret);

        $tampered = str_replace('signature=', 'signature=tampered', $signed);

        $this->assertFalse($this->signer->validate($tampered, $this->secret));
    }

    public function testValidateReturnsFalseForTamperedExpires(): void
    {
        $url = 'https://example.com/path';
        $signed = $this->signer->sign($url, $this->secret);

        $tampered = str_replace('expires=', 'expires=9999999999', $signed);

        $this->assertFalse($this->signer->validate($tampered, $this->secret));
    }
}

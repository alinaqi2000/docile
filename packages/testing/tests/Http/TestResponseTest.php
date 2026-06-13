<?php

declare(strict_types=1);

namespace Docile\Testing\Tests\Http;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Docile\Testing\Http\TestResponse::class)]
final class TestResponseTest extends TestCase
{
    public function testConstructorWrapsResponse(): void
    {
        $psrResponse = new Response(200, [], 'body');
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertSame('body', $testResponse->getBody());
    }

    public function testAssertStatusPassesWhenMatch(): void
    {
        $psrResponse = new Response(200);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertNull($testResponse->assertStatus(200));
    }

    public function testAssertStatusFailsWhenMismatch(): void
    {
        $psrResponse = new Response(404);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Expected status code 200, got 404');

        $testResponse->assertStatus(200);
    }

    public function testAssertOkPassesFor200(): void
    {
        $psrResponse = new Response(200);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertNull($testResponse->assertOk());
    }

    public function testAssertOkFailsForNon200(): void
    {
        $psrResponse = new Response(404);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $testResponse->assertOk();
    }

    public function testAssertCreatedPassesFor201(): void
    {
        $psrResponse = new Response(201);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertNull($testResponse->assertCreated());
    }

    public function testAssertCreatedFailsForNon201(): void
    {
        $psrResponse = new Response(200);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $testResponse->assertCreated();
    }

    public function testAssertNotFoundPassesFor404(): void
    {
        $psrResponse = new Response(404);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertNull($testResponse->assertNotFound());
    }

    public function testAssertNotFoundFailsForNon404(): void
    {
        $psrResponse = new Response(200);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $testResponse->assertNotFound();
    }

    public function testAssertUnprocessablePassesFor422(): void
    {
        $psrResponse = new Response(422);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertNull($testResponse->assertUnprocessable());
    }

    public function testAssertUnprocessableFailsForNon422(): void
    {
        $psrResponse = new Response(200);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $testResponse->assertUnprocessable();
    }

    public function testAssertHeaderPassesWhenMatch(): void
    {
        $psrResponse = new Response(200, ['X-Custom' => 'value']);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertNull($testResponse->assertHeader('X-Custom', 'value'));
    }

    public function testAssertHeaderFailsWhenMismatch(): void
    {
        $psrResponse = new Response(200, ['X-Custom' => 'value']);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage("Expected header 'X-Custom' to be 'other', got 'value'");

        $testResponse->assertHeader('X-Custom', 'other');
    }

    public function testAssertJsonPassesWithSubsetMatch(): void
    {
        $psrResponse = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'key' => 'value',
            'nested' => ['data' => 'here'],
        ]));
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertNull($testResponse->assertJson(['key' => 'value']));
    }

    public function testAssertJsonFailsWhenKeyMissing(): void
    {
        $psrResponse = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'key' => 'value',
        ]));
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage("JSON key 'missing' not found in response");

        $testResponse->assertJson(['missing' => 'value']);
    }

    public function testAssertJsonFailsWhenValueMismatch(): void
    {
        $psrResponse = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'key' => 'value',
        ]));
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage("JSON key 'key' expected to be");

        $testResponse->assertJson(['key' => 'other']);
    }

    public function testAssertJsonPathPassesWithDotNotation(): void
    {
        $psrResponse = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'user' => [
                'name' => 'John',
                'email' => 'john@example.com',
            ],
        ]));
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertNull($testResponse->assertJsonPath('user.name', 'John'));
    }

    public function testAssertJsonPathPassesWithArrayIndex(): void
    {
        $psrResponse = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'items' => ['first', 'second', 'third'],
        ]));
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertNull($testResponse->assertJsonPath('items.0', 'first'));
    }

    public function testAssertJsonPathFailsWhenPathNotFound(): void
    {
        $psrResponse = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'user' => ['name' => 'John'],
        ]));
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage("JSON path 'user.email' does not exist");

        $testResponse->assertJsonPath('user.email', 'john@example.com');
    }

    public function testAssertJsonPathFailsWhenValueMismatch(): void
    {
        $psrResponse = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'user' => ['name' => 'John'],
        ]));
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage("JSON path 'user.name' expected to be");

        $testResponse->assertJsonPath('user.name', 'Jane');
    }

    public function testAssertBodyContainsPassesWhenFound(): void
    {
        $psrResponse = new Response(200, [], 'Hello World');
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertNull($testResponse->assertBodyContains('Hello'));
    }

    public function testAssertBodyContainsFailsWhenNotFound(): void
    {
        $psrResponse = new Response(200, [], 'Hello World');
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage("Response body does not contain 'Missing'");

        $testResponse->assertBodyContains('Missing');
    }

    public function testGetJsonReturnsDecodedArray(): void
    {
        $data = ['key' => 'value', 'number' => 123];
        $psrResponse = new Response(200, ['Content-Type' => 'application/json'], json_encode($data));
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertSame($data, $testResponse->getJson());
    }

    public function testGetJsonThrowsOnInvalidJson(): void
    {
        $psrResponse = new Response(200, [], 'not json');
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Response body is not valid JSON');

        $testResponse->getJson();
    }

    public function testGetBodyReturnsString(): void
    {
        $psrResponse = new Response(200, [], 'body content');
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertSame('body content', $testResponse->getBody());
    }

    public function testGetStatusReturnsStatusCode(): void
    {
        $psrResponse = new Response(404);
        $testResponse = new \Docile\Testing\Http\TestResponse($psrResponse);

        $this->assertSame(404, $testResponse->getStatus());
    }


}

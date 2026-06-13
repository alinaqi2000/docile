<?php

declare(strict_types=1);

namespace Docile\Testing\Tests\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(\Docile\Testing\Http\TestRequest::class)]
final class TestRequestTest extends TestCase
{
    public function testFromCreatesInstance(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test');

        $this->assertInstanceOf(\Docile\Testing\Http\TestRequest::class, $request);
    }

    public function testWithHeaderReturnsNewInstance(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test');
        $newRequest = $request->withHeader('X-Custom', 'value');

        $this->assertNotSame($request, $newRequest);
    }

    public function testWithBodyReturnsNewInstance(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('POST', '/test');
        $newRequest = $request->withBody('body content');

        $this->assertNotSame($request, $newRequest);
    }

    public function testWithJsonBodyReturnsNewInstance(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('POST', '/test');
        $newRequest = $request->withJsonBody(['key' => 'value']);

        $this->assertNotSame($request, $newRequest);
    }

    public function testWithJsonBodyEncodesData(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('POST', '/test')
            ->withJsonBody(['key' => 'value']);

        $psrRequest = $request->build();
        $this->assertSame('{"key":"value"}', (string) $psrRequest->getBody());
    }

    public function testWithJsonBodySetsContentType(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('POST', '/test')
            ->withJsonBody(['key' => 'value']);

        $psrRequest = $request->build();
        $this->assertSame('application/json', $psrRequest->getHeaderLine('Content-Type'));
    }

    public function testWithJsonBodyThrowsOnInvalidData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode JSON data');

        $resource = fopen('php://memory', 'r');
        \Docile\Testing\Http\TestRequest::from('POST', '/test')
            ->withJsonBody($resource);
        fclose($resource);
    }

    public function testWithFormParamsReturnsNewInstance(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('POST', '/test');
        $newRequest = $request->withFormParams(['key' => 'value']);

        $this->assertNotSame($request, $newRequest);
    }

    public function testWithFormParamsEncodesData(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('POST', '/test')
            ->withFormParams(['key' => 'value']);

        $psrRequest = $request->build();
        $this->assertSame('key=value', (string) $psrRequest->getBody());
    }

    public function testWithQueryParamsReturnsNewInstance(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test');
        $newRequest = $request->withQueryParams(['key' => 'value']);

        $this->assertNotSame($request, $newRequest);
    }

    public function testWithQueryParamsAppendsToUri(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test')
            ->withQueryParams(['key' => 'value']);

        $psrRequest = $request->build();
        $this->assertNotEmpty($psrRequest->getQueryParams());
    }

    public function testWithCookieReturnsNewInstance(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test');
        $newRequest = $request->withCookie('session', 'abc123');

        $this->assertNotSame($request, $newRequest);
    }

    public function testWithCookieSetsCookieParam(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test')
            ->withCookie('session', 'abc123');

        $psrRequest = $request->build();
        $this->assertSame(['session' => 'abc123'], $psrRequest->getCookieParams());
    }

    public function testAsJsonReturnsNewInstance(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test');
        $newRequest = $request->asJson();

        $this->assertNotSame($request, $newRequest);
    }

    public function testAsJsonSetsContentType(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test')
            ->asJson();

        $psrRequest = $request->build();
        $this->assertSame('application/json', $psrRequest->getHeaderLine('Content-Type'));
    }

    public function testAsJsonDoesNotOverrideExistingContentType(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test')
            ->withHeader('Content-Type', 'text/plain')
            ->asJson();

        $psrRequest = $request->build();
        $this->assertSame('text/plain', $psrRequest->getHeaderLine('Content-Type'));
    }

    public function testBuildReturnsServerRequestInterface(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test');
        $psrRequest = $request->build();

        $this->assertInstanceOf(ServerRequestInterface::class, $psrRequest);
    }

    public function testBuildSetsMethod(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('POST', '/test');
        $psrRequest = $request->build();

        $this->assertSame('POST', $psrRequest->getMethod());
    }

    public function testBuildSetsUri(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test/path');
        $psrRequest = $request->build();

        $this->assertStringContainsString('/test/path', $psrRequest->getUri()->getPath());
    }

    public function testBuildSetsCustomHeader(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test')
            ->withHeader('X-Custom', 'value');
        $psrRequest = $request->build();

        $this->assertSame('value', $psrRequest->getHeaderLine('X-Custom'));
    }

    public function testBuildSetsBody(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('POST', '/test')
            ->withBody('custom body');
        $psrRequest = $request->build();

        $this->assertSame('custom body', (string) $psrRequest->getBody());
    }

    public function testBuildChainsMultipleHeaders(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test')
            ->withHeader('X-One', 'value1')
            ->withHeader('X-Two', 'value2');
        $psrRequest = $request->build();

        $this->assertSame('value1', $psrRequest->getHeaderLine('X-One'));
        $this->assertSame('value2', $psrRequest->getHeaderLine('X-Two'));
    }

    public function testBuildWithMultipleQueryParams(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test')
            ->withQueryParams(['key1' => 'value1', 'key2' => 'value2']);
        $psrRequest = $request->build();

        $queryParams = $psrRequest->getQueryParams();
        $this->assertNotEmpty($queryParams);
    }

    public function testBuildWithMultipleCookies(): void
    {
        $request = \Docile\Testing\Http\TestRequest::from('GET', '/test')
            ->withCookie('cookie1', 'value1')
            ->withCookie('cookie2', 'value2');
        $psrRequest = $request->build();

        $cookies = $psrRequest->getCookieParams();
        $this->assertArrayHasKey('cookie1', $cookies);
        $this->assertArrayHasKey('cookie2', $cookies);
    }
}

<?php

declare(strict_types=1);

namespace Docile\Http\Tests;

use Nyholm\Psr7Server\ServerRequestCreator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequestFactoryTest extends TestCase
{
    public function testFromGlobalsReturnsServerRequest(): void
    {
        // Backup and set up superglobals
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_HOST' => 'example.com',
        ];
        $_GET = ['param' => 'value'];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];

        $request = \Docile\Http\ServerRequestFactory::fromGlobals();

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/test', $request->getUri()->getPath());
        $this->assertSame('example.com', $request->getUri()->getHost());
        $this->assertSame(['param' => 'value'], $request->getQueryParams());
    }

    public function testFromGlobalsWithPostData(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/submit',
            'HTTP_HOST' => 'example.com',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ];
        $_POST = ['name' => 'John', 'email' => 'john@example.com'];
        $_GET = $_COOKIE = $_FILES = [];

        $request = \Docile\Http\ServerRequestFactory::fromGlobals();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/submit', $request->getUri()->getPath());
        $this->assertSame(['name' => 'John', 'email' => 'john@example.com'], $request->getParsedBody());
    }

    public function testFromGlobalsWithHeaders(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'example.com',
            'HTTP_USER_AGENT' => 'Test-Agent/1.0',
            'HTTP_ACCEPT' => 'application/json',
        ];
        $_GET = $_POST = $_COOKIE = $_FILES = [];

        $request = \Docile\Http\ServerRequestFactory::fromGlobals();

        $this->assertSame('Test-Agent/1.0', $request->getHeaderLine('User-Agent'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function testFromGlobalsWithCookies(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_HOST' => 'example.com',
        ];
        $_COOKIE = ['session_id' => 'abc123', 'theme' => 'dark'];
        $_GET = $_POST = $_FILES = [];

        $request = \Docile\Http\ServerRequestFactory::fromGlobals();

        $this->assertSame(['session_id' => 'abc123', 'theme' => 'dark'], $request->getCookieParams());
    }

    public function testFromGlobalsDelegatesToNyholm(): void
    {
        // This test verifies that our factory is just a thin wrapper
        // by comparing the output with direct Nyholm usage
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'HTTP_HOST' => 'example.com',
        ];
        $_GET = $_POST = $_COOKIE = $_FILES = [];

        $ourRequest = \Docile\Http\ServerRequestFactory::fromGlobals();

        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $nyholmRequest = $creator->fromGlobals();

        $this->assertSame(
            $nyholmRequest->getMethod(),
            $ourRequest->getMethod()
        );
        $this->assertSame(
            $nyholmRequest->getUri()->__toString(),
            $ourRequest->getUri()->__toString()
        );
        $this->assertSame(
            $nyholmRequest->getQueryParams(),
            $ourRequest->getQueryParams()
        );
    }
}
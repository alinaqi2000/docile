<?php

declare(strict_types=1);

namespace Docile\Http\Tests\Exception;

use Docile\Http\Exception\HttpException;
use Docile\Http\Exception\MethodNotAllowedHttpException;
use PHPUnit\Framework\TestCase;

class MethodNotAllowedHttpExceptionTest extends TestCase
{
    public function testExtendsHttpException(): void
    {
        $exception = new MethodNotAllowedHttpException(['GET', 'POST']);

        $this->assertInstanceOf(HttpException::class, $exception);
    }

    public function testConstructorWithDefaults(): void
    {
        $allowedMethods = ['GET', 'POST'];
        $exception = new MethodNotAllowedHttpException($allowedMethods);

        $this->assertSame('Method Not Allowed', $exception->getMessage());
        $this->assertSame(405, $exception->getStatusCode());
        $this->assertSame(['Allow' => 'GET, POST'], $exception->getHeaders());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithCustomMessage(): void
    {
        $allowedMethods = ['GET', 'POST', 'PUT'];
        $message = 'Only GET, POST, and PUT methods are allowed';
        $exception = new MethodNotAllowedHttpException($allowedMethods, $message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(405, $exception->getStatusCode());
        $this->assertSame(['Allow' => 'GET, POST, PUT'], $exception->getHeaders());
    }

    public function testConstructorWithPrevious(): void
    {
        $allowedMethods = ['GET'];
        $previous = new \RuntimeException('Router error');
        $exception = new MethodNotAllowedHttpException($allowedMethods, 'Method not allowed', $previous);

        $this->assertSame('Method not allowed', $exception->getMessage());
        $this->assertSame(405, $exception->getStatusCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testGetAllowedMethods(): void
    {
        $testCases = [
            ['GET'],
            ['GET', 'POST'],
            ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
            ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'],
        ];

        foreach ($testCases as $allowedMethods) {
            $exception = new MethodNotAllowedHttpException($allowedMethods);
            $this->assertSame($allowedMethods, $exception->getAllowedMethods());
        }
    }

    public function testStatusCodeIsAlways405(): void
    {
        $exception = new MethodNotAllowedHttpException(['GET', 'POST']);

        $this->assertSame(405, $exception->getStatusCode());
    }

    public function testAllowHeaderIsCorrectlyFormatted(): void
    {
        $testCases = [
            [['GET'], 'GET'],
            [['GET', 'POST'], 'GET, POST'],
            [['GET', 'POST', 'PUT'], 'GET, POST, PUT'],
        ];

        foreach ($testCases as [$allowedMethods, $expectedHeaderValue]) {
            $exception = new MethodNotAllowedHttpException($allowedMethods);
            $headers = $exception->getHeaders();
            
            $this->assertArrayHasKey('Allow', $headers);
            $this->assertSame($expectedHeaderValue, $headers['Allow']);
        }
    }

    public function testEmptyAllowedMethods(): void
    {
        $exception = new MethodNotAllowedHttpException([]);

        $this->assertSame([], $exception->getAllowedMethods());
        $this->assertSame(['Allow' => ''], $exception->getHeaders());
    }

    public function testSingleAllowedMethod(): void
    {
        $exception = new MethodNotAllowedHttpException(['HEAD']);

        $this->assertSame(['HEAD'], $exception->getAllowedMethods());
        $this->assertSame(['Allow' => 'HEAD'], $exception->getHeaders());
    }

    public function testImplementsHttpExceptionInterface(): void
    {
        $exception = new MethodNotAllowedHttpException(['GET']);

        $this->assertInstanceOf(\Docile\Http\Exception\HttpExceptionInterface::class, $exception);
    }

    public function testIsRuntimeException(): void
    {
        $exception = new MethodNotAllowedHttpException(['GET']);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testThrowable(): void
    {
        $exception = new MethodNotAllowedHttpException(['GET']);

        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testCanBeCaughtAsHttpException(): void
    {
        $exception = new MethodNotAllowedHttpException(['GET', 'POST']);

        try {
            throw $exception;
        } catch (HttpException $e) {
            $this->assertSame(405, $e->getStatusCode());
            $this->assertSame(['GET', 'POST'], $e->getAllowedMethods());
        }
    }

    public function testCanBeCaughtAsHttpExceptionInterface(): void
    {
        $exception = new MethodNotAllowedHttpException(['GET', 'POST']);

        try {
            throw $exception;
        } catch (\Docile\Http\Exception\HttpExceptionInterface $e) {
            $this->assertSame(405, $e->getStatusCode());
            $this->assertSame(['GET', 'POST'], $e->getAllowedMethods());
        }
    }

    public function testAllowedMethodsAreCaseSensitive(): void
    {
        $allowedMethods = ['get', 'POST']; // Mixed case
        $exception = new MethodNotAllowedHttpException($allowedMethods);

        $this->assertSame(['get', 'POST'], $exception->getAllowedMethods());
        $this->assertSame(['Allow' => 'get, POST'], $exception->getHeaders());
    }
}
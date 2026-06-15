<?php

declare(strict_types=1);

namespace Docile\Http\Tests\Exception;

use Docile\Http\Exception\HttpException;
use Docile\Http\Exception\HttpExceptionInterface;
use PHPUnit\Framework\TestCase;

class HttpExceptionTest extends TestCase
{
    public function testImplementsHttpExceptionInterface(): void
    {
        $exception = new HttpException();

        $this->assertInstanceOf(HttpExceptionInterface::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testConstructorWithDefaults(): void
    {
        $exception = new HttpException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame(500, $exception->getStatusCode());
        $this->assertSame([], $exception->getHeaders());
    }

    public function testConstructorWithMessage(): void
    {
        $message = 'An error occurred';
        $exception = new HttpException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(500, $exception->getStatusCode());
        $this->assertSame([], $exception->getHeaders());
    }

    public function testConstructorWithMessageAndCode(): void
    {
        $message = 'Validation failed';
        $code = 123;
        $exception = new HttpException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame(500, $exception->getStatusCode());
        $this->assertSame([], $exception->getHeaders());
    }

    public function testConstructorWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new HttpException('Current error', 0, $previous);

        $this->assertSame('Current error', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testConstructorWithStatusCode(): void
    {
        $statusCode = 404;
        $exception = new HttpException('Not found', 0, null, $statusCode);

        $this->assertSame($statusCode, $exception->getStatusCode());
        $this->assertSame('Not found', $exception->getMessage());
    }

    public function testConstructorWithHeaders(): void
    {
        $headers = ['Content-Type' => ['application/json'], 'X-Custom' => ['value']];
        $exception = new HttpException('Error', 0, null, 500, $headers);

        $this->assertSame($headers, $exception->getHeaders());
        $this->assertSame('Error', $exception->getMessage());
        $this->assertSame(500, $exception->getStatusCode());
    }

    public function testConstructorWithAllParameters(): void
    {
        $message = 'Bad request';
        $code = 400;
        $previous = new \InvalidArgumentException('Invalid input');
        $statusCode = 400;
        $headers = ['Content-Type' => ['application/problem+json']];

        $exception = new HttpException($message, $code, $previous, $statusCode, $headers);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($statusCode, $exception->getStatusCode());
        $this->assertSame($headers, $exception->getHeaders());
    }

    public function testGetStatusCode(): void
    {
        $statusCodes = [400, 401, 403, 404, 500, 502, 503];

        foreach ($statusCodes as $statusCode) {
            $exception = new HttpException('Error', 0, null, $statusCode);
            $this->assertSame($statusCode, $exception->getStatusCode());
        }
    }

    public function testGetHeaders(): void
    {
        $testCases = [
            [],
            ['X-Test' => ['value']],
            ['Content-Type' => ['application/json'], 'X-Custom' => ['value1', 'value2']],
        ];

        foreach ($testCases as $headers) {
            $exception = new HttpException('Error', 0, null, 500, $headers);
            $this->assertSame($headers, $exception->getHeaders());
        }
    }

    public function testHeadersAreReadonly(): void
    {
        $headers = ['X-Test' => ['value']];
        $exception = new HttpException('Error', 0, null, 500, $headers);

        // Modify the original array
        $headers['X-New'] = ['new-value'];

        // Exception headers should remain unchanged
        $this->assertSame(['X-Test' => ['value']], $exception->getHeaders());
    }

    public function testStatusCodeIsReadonly(): void
    {
        $exception = new HttpException('Error', 0, null, 404);

        // The status code should be accessible but not modifiable
        $this->assertSame(404, $exception->getStatusCode());
        
        // There's no setter, so we can't modify it - this is just a sanity check
        $this->assertSame(404, $exception->getStatusCode());
    }
}
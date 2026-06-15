<?php

declare(strict_types=1);

namespace Docile\Http\Tests\Exception;

use Docile\Http\Exception\HttpException;
use Docile\Http\Exception\NotFoundHttpException;
use PHPUnit\Framework\TestCase;

class NotFoundHttpExceptionTest extends TestCase
{
    public function testExtendsHttpException(): void
    {
        $exception = new NotFoundHttpException();

        $this->assertInstanceOf(HttpException::class, $exception);
    }

    public function testConstructorWithDefaults(): void
    {
        $exception = new NotFoundHttpException();

        $this->assertSame('Not Found', $exception->getMessage());
        $this->assertSame(404, $exception->getStatusCode());
        $this->assertSame([], $exception->getHeaders());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithCustomMessage(): void
    {
        $message = 'Resource not found';
        $exception = new NotFoundHttpException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(404, $exception->getStatusCode());
        $this->assertSame([], $exception->getHeaders());
    }

    public function testConstructorWithPrevious(): void
    {
        $previous = new \RuntimeException('Database error');
        $exception = new NotFoundHttpException('User not found', $previous);

        $this->assertSame('User not found', $exception->getMessage());
        $this->assertSame(404, $exception->getStatusCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testStatusCodeIsAlways404(): void
    {
        $exception = new NotFoundHttpException('Custom message');

        $this->assertSame(404, $exception->getStatusCode());
    }

    public function testHeadersAreAlwaysEmpty(): void
    {
        $exception = new NotFoundHttpException();

        $this->assertSame([], $exception->getHeaders());
    }

    public function testImplementsHttpExceptionInterface(): void
    {
        $exception = new NotFoundHttpException();

        $this->assertInstanceOf(\Docile\Http\Exception\HttpExceptionInterface::class, $exception);
    }

    public function testIsRuntimeException(): void
    {
        $exception = new NotFoundHttpException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testThrowable(): void
    {
        $exception = new NotFoundHttpException();

        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testCanBeCaughtAsHttpException(): void
    {
        $exception = new NotFoundHttpException();

        try {
            throw $exception;
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
            $this->assertSame('Not Found', $e->getMessage());
        }
    }

    public function testCanBeCaughtAsHttpExceptionInterface(): void
    {
        $exception = new NotFoundHttpException();

        try {
            throw $exception;
        } catch (\Docile\Http\Exception\HttpExceptionInterface $e) {
            $this->assertSame(404, $e->getStatusCode());
            $this->assertSame('Not Found', $e->getMessage());
        }
    }
}
<?php

declare(strict_types=1);

namespace Docile\Http\Tests\Exception;

use Docile\Http\Exception\EmitterException;
use PHPUnit\Framework\TestCase;

class EmitterExceptionTest extends TestCase
{
    public function testExtendsRuntimeException(): void
    {
        $exception = new EmitterException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testConstructorWithDefaults(): void
    {
        $exception = new EmitterException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithMessage(): void
    {
        $message = 'Headers already sent';
        $exception = new EmitterException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithMessageAndCode(): void
    {
        $message = 'Failed to emit response';
        $code = 500;
        $exception = new EmitterException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithPrevious(): void
    {
        $previous = new \RuntimeException('Output already started');
        $exception = new EmitterException('Cannot emit headers', 0, $previous);

        $this->assertSame('Cannot emit headers', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testConstructorWithAllParameters(): void
    {
        $message = 'Emitter error';
        $code = 1;
        $previous = new \LogicException('Invalid state');

        $exception = new EmitterException($message, $code, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testThrowable(): void
    {
        $exception = new EmitterException();

        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testCanBeCaughtAsRuntimeException(): void
    {
        $exception = new EmitterException('Test error');

        try {
            throw $exception;
        } catch (\RuntimeException $e) {
            $this->assertSame('Test error', $e->getMessage());
        }
    }

    public function testCanBeCaughtAsException(): void
    {
        $exception = new EmitterException('Test error');

        try {
            throw $exception;
        } catch (\Exception $e) {
            $this->assertSame('Test error', $e->getMessage());
        }
    }

    public function testCanBeCaughtAsThrowable(): void
    {
        $exception = new EmitterException('Test error');

        try {
            throw $exception;
        } catch (\Throwable $e) {
            $this->assertSame('Test error', $e->getMessage());
        }
    }

    public function testCommonUseCases(): void
    {
        $testCases = [
            'Headers already sent',
            'Output started before response emission',
            'Cannot modify header information - headers already sent',
        ];

        foreach ($testCases as $message) {
            $exception = new EmitterException($message);
            $this->assertSame($message, $exception->getMessage());
        }
    }

    public function testExceptionChain(): void
    {
        $original = new \RuntimeException('Original error');
        $emitterException = new EmitterException('Emitter failed', 0, $original);

        $this->assertSame($original, $emitterException->getPrevious());
        $this->assertSame('Original error', $emitterException->getPrevious()->getMessage());
    }
}
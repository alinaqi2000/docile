<?php

declare(strict_types=1);

namespace Docile\Testing\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Docile\Testing\Exception\TestingException::class)]
final class TestingExceptionTest extends TestCase
{
    public function testExceptionIsRuntimeException(): void
    {
        $exception = new \Docile\Testing\Exception\TestingException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionCanBeCreatedWithMessage(): void
    {
        $exception = new \Docile\Testing\Exception\TestingException('Test message');

        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testExceptionCanBeCreatedWithCode(): void
    {
        $exception = new \Docile\Testing\Exception\TestingException('Test message', 123);

        $this->assertSame(123, $exception->getCode());
    }

    public function testExceptionCanBeCreatedWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous');
        $exception = new \Docile\Testing\Exception\TestingException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}

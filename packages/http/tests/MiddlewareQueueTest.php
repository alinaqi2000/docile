<?php

declare(strict_types=1);

namespace Docile\Http\Tests;

use Docile\Http\MiddlewareQueue;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;

class MiddlewareQueueTest extends TestCase
{
    public function testConstructorWithEmptyArray(): void
    {
        $queue = new MiddlewareQueue();

        $this->assertSame([], $queue->all());
    }

    public function testConstructorWithMiddleware(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $middleware3 = 'TestMiddleware';

        $queue = new MiddlewareQueue([$middleware1, $middleware2, $middleware3]);

        $all = $queue->all();
        $this->assertCount(3, $all);
        $this->assertSame($middleware1, $all[0]);
        $this->assertSame($middleware2, $all[1]);
        $this->assertSame($middleware3, $all[2]);
    }

    public function testAppendWithMiddlewareObject(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);

        $queue = new MiddlewareQueue([$middleware1]);
        $newQueue = $queue->append($middleware2);

        // Original queue should be unchanged
        $this->assertSame([$middleware1], $queue->all());

        // New queue should have appended middleware
        $this->assertSame([$middleware1, $middleware2], $newQueue->all());
    }

    public function testAppendWithMiddlewareString(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = 'TestMiddleware';

        $queue = new MiddlewareQueue([$middleware1]);
        $newQueue = $queue->append($middleware2);

        $this->assertSame([$middleware1], $queue->all());
        $this->assertSame([$middleware1, $middleware2], $newQueue->all());
    }

    public function testPrependWithMiddlewareObject(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);

        $queue = new MiddlewareQueue([$middleware1]);
        $newQueue = $queue->prepend($middleware2);

        // Original queue should be unchanged
        $this->assertSame([$middleware1], $queue->all());

        // New queue should have prepended middleware
        $this->assertSame([$middleware2, $middleware1], $newQueue->all());
    }

    public function testPrependWithMiddlewareString(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = 'TestMiddleware';

        $queue = new MiddlewareQueue([$middleware1]);
        $newQueue = $queue->prepend($middleware2);

        $this->assertSame([$middleware1], $queue->all());
        $this->assertSame([$middleware2, $middleware1], $newQueue->all());
    }

    public function testChainingOperations(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $middleware3 = 'TestMiddleware';

        $queue = new MiddlewareQueue([$middleware1]);
        $newQueue = $queue
            ->append($middleware2)
            ->prepend($middleware3)
            ->append('AnotherMiddleware');

        $this->assertSame([$middleware1], $queue->all());
        $this->assertSame(
            [$middleware3, $middleware1, $middleware2, 'AnotherMiddleware'],
            $newQueue->all()
        );
    }

    public function testImmutability(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);

        $queue = new MiddlewareQueue([$middleware1]);
        $queue2 = $queue->append($middleware2);
        $queue3 = $queue->prepend($middleware2);

        // All queues should be different instances
        $this->assertNotSame($queue, $queue2);
        $this->assertNotSame($queue, $queue3);
        $this->assertNotSame($queue2, $queue3);

        // Original queue should be unchanged
        $this->assertSame([$middleware1], $queue->all());

        // Each queue should have its own state
        $this->assertSame([$middleware1, $middleware2], $queue2->all());
        $this->assertSame([$middleware2, $middleware1], $queue3->all());
    }

    public function testMixedMiddlewareTypes(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = 'TestMiddleware';
        $middleware3 = $this->createMock(MiddlewareInterface::class);
        $middleware4 = 'AnotherMiddleware';

        $queue = new MiddlewareQueue([$middleware1, $middleware2]);
        $newQueue = $queue->append($middleware3)->prepend($middleware4);

        $this->assertSame(
            [$middleware4, $middleware1, $middleware2, $middleware3],
            $newQueue->all()
        );
    }
}
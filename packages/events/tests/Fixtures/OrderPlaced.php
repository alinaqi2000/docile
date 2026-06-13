<?php

declare(strict_types=1);

namespace Docile\Events\Tests\Fixtures;

/**
 * Plain (non-stoppable) domain event fixture for testing.
 */
final class OrderPlaced
{
    public function __construct(
        public readonly int $orderId,
    ) {}
}

<?php

declare(strict_types=1);

namespace Docile\Events\Tests\Fixtures;

use Docile\Events\Attribute\AsListener;

/**
 * Subscriber fixture with multiple #[AsListener] methods at different priorities.
 *
 * Used to verify that subscribe() correctly auto-registers methods with the
 * appropriate event class and priority.
 */
final class PrioritySubscriber
{
    /** @var list<string> */
    public array $log = [];

    #[AsListener(event: OrderPlaced::class, priority: 10)]
    public function onOrderPlacedHigh(OrderPlaced $event): void
    {
        $this->log[] = 'high:' . $event->orderId;
    }

    #[AsListener(event: OrderPlaced::class, priority: 5)]
    public function onOrderPlacedMedium(OrderPlaced $event): void
    {
        $this->log[] = 'medium:' . $event->orderId;
    }

    #[AsListener(event: OrderPlaced::class, priority: 0)]
    public function onOrderPlacedLow(OrderPlaced $event): void
    {
        $this->log[] = 'low:' . $event->orderId;
    }

    #[AsListener(event: UserRegistered::class, priority: 1)]
    public function onUserRegistered(UserRegistered $event): void
    {
        $this->log[] = 'user:' . $event->email;
    }
}

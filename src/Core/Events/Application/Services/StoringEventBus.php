<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Events\Application\Services;

use TMT\CRM\Core\Events\Domain\Contracts\EventBusInterface;
use TMT\CRM\Core\Events\Domain\Contracts\EventInterface;
use TMT\CRM\Core\Events\Domain\Contracts\EventSubscriberInterface;
use TMT\CRM\Domain\Repositories\EventStoreRepositoryInterface;

final class StoringEventBus implements EventBusInterface
{
    public function __construct(
        private EventBusInterface $inner,
        private EventStoreRepositoryInterface $store
    ) {}

    public function publish(EventInterface $event): void
    {
        // 1) Ghi vào event store (audit trail)
        try {
            $this->store->append($event);
        } catch (\Throwable $e) {
            // Không chặn publish; vẫn phát event để hệ thống hoạt động
            error_log('[EventBus] Failed to append event to store: ' . $e->getMessage());
        }

        // 2) Phát tới các subscriber
        $this->inner->publish($event);
    }

    public function subscribe(string $event_name, EventSubscriberInterface $subscriber, int $priority = 10): void
    {
        $this->inner->subscribe($event_name, $subscriber, $priority);
    }
}

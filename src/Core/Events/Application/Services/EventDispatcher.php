<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Events\Application\Services;

use TMT\CRM\Core\Events\Domain\Contracts\{EventBusInterface, EventInterface, EventSubscriberInterface};

final class EventDispatcher implements EventBusInterface
{
    /** @var array<string, array<int, EventSubscriberInterface[]>> */
    private array $listeners = [];

    public function publish(EventInterface $event): void
    {
        $name = $event->name();
        if (empty($this->listeners[$name])) {
            return;
        }
        krsort($this->listeners[$name]); // ưu tiên lớn trước
        foreach ($this->listeners[$name] as $priority => $subs) {
            foreach ($subs as $subscriber) {
                try {
                    $subscriber->handle($event);
                } catch (\Throwable $e) {
                    error_log('[EventBus] Subscriber error for ' . $name . ': ' . $e->getMessage());
                }
            }
        }
    }

    public function subscribe(string $event_name, EventSubscriberInterface $subscriber, int $priority = 10): void
    {
        $this->listeners[$event_name][$priority][] = $subscriber;
    }
}

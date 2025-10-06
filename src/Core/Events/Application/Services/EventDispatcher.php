<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Events\Application\Services;

use TMT\CRM\Core\Events\Domain\Contracts\EventBusInterface;
use TMT\CRM\Core\Events\Domain\Contracts\EventInterface;
use TMT\CRM\Core\Events\Domain\Contracts\EventSubscriberInterface;

final class EventDispatcher implements EventBusInterface
{
    /**
     * @var array<string, array<int, EventSubscriberInterface[]>>
     *   $listeners[event_name][priority][] = subscriber
     */
    private array $listeners = [];

    public function publish(EventInterface $event): void
    {
        $name = $event->name();
        if (empty($this->listeners[$name])) {
            return;
        }

        // Ưu tiên: số nhỏ chạy trước (giống WordPress hooks)
        ksort($this->listeners[$name], \SORT_NUMERIC);

        foreach ($this->listeners[$name] as $priority => $subs) {
            foreach ($subs as $subscriber) {
                try {
                    $subscriber->handle($event);
                } catch (\Throwable $e) {
                    // Không ném lỗi để tránh làm hỏng luồng chính
                    error_log(sprintf('[EventBus] Subscriber error for %s@%d: %s',
                        $name,
                        (int)$priority,
                        $e->getMessage()
                    ));
                }
            }
        }
    }

    public function subscribe(string $event_name, EventSubscriberInterface $subscriber, int $priority = 10): void
    {
        if (!isset($this->listeners[$event_name][$priority])) {
            $this->listeners[$event_name][$priority] = [];
        }
        $this->listeners[$event_name][$priority][] = $subscriber;
    }
}

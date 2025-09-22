<?php

/**
 * EventBusBridge (file chính)
 * Bridge WP action -> EventBus::publish()
 */

declare(strict_types=1);

namespace TMT\CRM\Shared\EventBus;

final class EventBusBridge
{
    public static function register(): void
    {
        // Cho phép phát sự kiện qua: do_action('tmt_event_bus', $event, $payload);
        add_action('tmt_event_bus', static function (string $event, $payload = null): void {
            EventBus::publish($event, $payload);
        }, 10, 2);
    }
}

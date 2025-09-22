<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\EventBus;

/**
 * EventBus tối giản: subscribe/publish theo tên event.
 * - Ưu tiên số nhỏ chạy trước (priority mặc định = 10).
 * - Bắt exception và ghi log, không làm chết request.
 */
final class EventBus
{
    /** @var array<string, array<int, array{priority:int, listener:callable}>> */
    private static array $subscribers = [];

    public static function subscribe(string $event, callable $listener, int $priority = 10): void
    {
        self::$subscribers[$event] ??= [];
        self::$subscribers[$event][] = ['priority' => $priority, 'listener' => $listener];
    }

    /** @param mixed $payload */
    public static function publish(string $event, $payload = null): void
    {
        if (empty(self::$subscribers[$event])) {
            return;
        }

        // Sắp xếp theo priority tăng dần
        usort(self::$subscribers[$event], static function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        foreach (self::$subscribers[$event] as $item) {
            try {
                ($item['listener'])($payload);
            } catch (\Throwable $e) {
                error_log(sprintf(
                    '[EventBus] Subscriber error for %s: %s',
                    $event,
                    $e->getMessage()
                ));
            }
        }
    }
}

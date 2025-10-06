<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Events\Infrastructure\Providers;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Events\Domain\Contracts\EventBusInterface;
use TMT\CRM\Core\Events\Domain\Contracts\EventSubscriberInterface;

/**
 * Đăng ký nhiều subscriber theo chuẩn `subscribed_events(): array`.
 */
final class SubscriberLoader
{
    /**
     * @param array<class-string<EventSubscriberInterface>> $subscriber_classes
     */
    public static function register_many(array $subscriber_classes): void
    {
        /** @var EventBusInterface $bus */
        $bus = Container::get(EventBusInterface::class);

        foreach ($subscriber_classes as $class) {
            /** @var EventSubscriberInterface $subscriber */
            $subscriber = Container::get($class);
            $map = $subscriber::subscribed_events();
            foreach (self::normalize($map) as [$event, $priority]) {
                $bus->subscribe($event, $subscriber, $priority);
            }
        }
    }

    /**
     * Chuẩn hóa map events → priority thành mảng [[event, priority], ...]
     * Chấp nhận cả hai dạng:
     * - ['EventName' => 10, 'AnotherEvent' => 5]
     * - [['event' => 'EventName', 'priority' => 10], ...]
     *
     * @param array<int|string, mixed> $map
     * @return array<int, array{0:string,1:int}>
     */
    private static function normalize(array $map): array
    {
        $normalized = [];
        foreach ($map as $k => $v) {
            if (is_array($v) && isset($v['event'])) {
                $normalized[] = [(string)$v['event'], (int)($v['priority'] ?? 10)];
            } else {
                // dạng 'EventName' => 10 (hoặc => null)
                $normalized[] = [is_string($k) ? $k : (string)$v, is_int($v) ? $v : 10];
            }
        }
        return $normalized;
    }
}

<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Presentation\Subscribers;

use TMT\CRM\Core\Events\Domain\Contracts\EventInterface;
use TMT\CRM\Core\Events\Domain\Contracts\EventSubscriberInterface;
use TMT\CRM\Core\Notifications\Application\Services\NotificationDispatcher;

final class EventDefaultSubscriber implements EventSubscriberInterface
{
    public function __construct(private NotificationDispatcher $dispatcher) {}

    public static function subscribed_events(): array
    {
        // Lắng nghe CompanyCreated qua DefaultEvent
        return ['CompanyCreated' => 10];
    }

    public function handle(EventInterface $event): void
    {
        // Lấy payload từ event: có thể là array|object (bạn đang truyền (object)['company'=>$dto])
        $payload = $event->payload();          // array|object
        $meta    = method_exists($event, 'metadata') ? $event->metadata() : null;

        // Chuẩn hoá context: đưa cả metadata vào để template có thể dùng {{meta.event_id}}...
        $context = is_array($payload) ? $payload : (array)$payload;
        if ($meta) {
            // metadata thường là VO; ép về array “mềm”
            $context['meta'] = [
                'event_id'       => $meta->event_id ?? null,
                'occurred_at'    => $meta->occurred_at?->format('c') ?? null,
                'actor_id'       => $meta->actor_id ?? null,
                'correlation_id' => $meta->correlation_id ?? null,
            ];
        }

        // Đưa đúng format mà NotificationDispatcher đang nhận
        $this->dispatcher->handle([
            'event_key' => $event->name(),   // "CompanyCreated"
            'context'   => $context,         // ['company'=>..., 'meta'=>...]
        ]);
    }
}

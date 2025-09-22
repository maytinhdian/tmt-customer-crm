<?php
// ============================================================================
// File: src/Core/Notifications/Application/Services/NotificationDispatcher.php (cập nhật on_event)
// ============================================================================


declare(strict_types=1);


namespace TMT\CRM\Core\Notifications\Application\Services;


use TMT\CRM\Core\Notifications\Domain\DTO\{NotificationDTO, DeliveryDTO, EventContextDTO};
use TMT\CRM\Domain\Repositories\{NotificationRepositoryInterface, DeliveryRepositoryInterface, TemplateRepositoryInterface};
use TMT\CRM\Core\Notifications\Domain\EventKeys;

final class NotificationDispatcher
{
    public function __construct(
        private NotificationRepositoryInterface $notifications,
        private DeliveryRepositoryInterface $deliveries,
        private TemplateRepositoryInterface $templates,
        private PreferenceService $preferences,
        private TemplateRenderer $renderer,
        private DeliveryService $sender
    ) {}

    /** Điểm vào khi có event domain */
    public function on_event(string $event_key, EventContextDTO $ctx): void
    {
        // 0) Chuẩn hoá entity
        $entity_type = $this->guess_entity_type($event_key); // ví dụ 'company' cho Company*
        $entity_id = (int) ($ctx->payload['company_id'] ?? 0);


        // 1) Tạo notification tổng quát (summary ngắn)
        $n = new NotificationDTO();
        $n->event_key = $event_key;
        $n->entity_type = $entity_type;
        $n->entity_id = $entity_id;
        $n->template_key = '';
        $n->summary = $this->build_summary($event_key, $ctx);
        $n->created_at = $ctx->occurred_at ?: current_time('mysql');
        $n->created_by = $ctx->actor_id;
        $notification_id = $this->notifications->create($n);


        // 2) Xây context render (placeholders)
        $context = $this->build_context_placeholders($event_key, $ctx);


        // 3) Chọn người nhận: actor + tất cả admin (demo P0)
        $recipients = $this->resolve_recipients_basic($ctx->actor_id);


        // 4) Duyệt từng người nhận → kênh nào bật theo preference → render theo template → tạo delivery + gửi
        foreach ($recipients as $user_id) {
            $channels = $this->preferences->channels_for_user($user_id, $event_key);
            foreach ($channels as $channel => $enabled) {
                if (!$enabled) continue;
                // Lấy template theo key: "{$event_key}:{$channel}"
                $tpl_key = $event_key . ':' . $channel;
                $tpl = $this->templates->find_by_key($tpl_key);
                if (!$tpl) continue; // không có template cho kênh này


                if (!PolicyGuard::can_receive($entity_type, $entity_id, $user_id)) {
                    continue;
                }


                $rendered = $this->renderer->render($tpl, $context);


                $d = new DeliveryDTO();
                $d->notification_id = $notification_id;
                $d->channel = $channel; // 'notice'|'email'|...
                $d->recipient_type = 'user';
                $d->recipient_value = (string) $user_id;
                $d->status = 'queued';
                $delivery_id = $this->deliveries->create($d);
                $d->id = $delivery_id;


                // Gửi ngay (P0)
                $this->sender->send($d, $rendered);
            }
        }
    }
    private function guess_entity_type(string $event_key): string
    {
        if (str_starts_with($event_key, 'Company')) return 'company';
        if (str_starts_with($event_key, 'Quote')) return 'quote';
        return 'entity';
    }


    private function build_summary(string $event_key, EventContextDTO $ctx): string
    {
        return match ($event_key) {
            EventKeys::COMPANY_CREATED => 'Company mới được tạo',
            EventKeys::COMPANY_SOFT_DELETED => 'Company bị xoá mềm',
            EventKeys::QUOTE_SENT => 'Đã gửi báo giá',
            default => $event_key,
        };
    }


    private function build_context_placeholders(string $event_key, EventContextDTO $ctx): array
    {
        $company_name = (string) ($ctx->payload['company_name'] ?? '');
        return [
            'actor_id' => (string)$ctx->actor_id,
            'occurred_at' => (string)$ctx->occurred_at,
            'company_id' => (string)($ctx->payload['company_id'] ?? ''),
            'company_name' => $company_name,
        ];
    }


    /**
     * P0: người nhận cơ bản = actor + tất cả admin
     * Sau này thay bằng PreferenceService/Rule nâng cao
     * @return int[] user ids
     */
    private function resolve_recipients_basic(int $actor_id): array
    {
        $ids = [$actor_id];
        $admins = get_users(['role' => 'administrator', 'fields' => 'ID']);
        foreach ($admins as $id) {
            if (!in_array((int)$id, $ids, true)) $ids[] = (int)$id;
        }
        return $ids;
    }
}

<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Application\Services;

use TMT\CRM\Core\Notifications\Domain\DTO\EventContextDTO;
use TMT\CRM\Core\Notifications\Domain\DTO\TemplateDTO;
use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;
use TMT\CRM\Core\Notifications\Domain\DTO\NotificationDTO;

/**
 * Nhận event → lấy template → render → gửi qua DeliveryService.
 * P0: không lưu DB để gọn. P1 sẽ ghép repo lưu Notification/Delivery.
 */
final class NotificationDispatcher
{
    public function __construct(
        private TemplateRenderer $renderer,
        private DeliveryService $delivery,
        private PreferenceService $preferences
    ) {}

    /** Callback cho EventBus (subscribe trong ServiceProvider) */
    // public function handle(array|object $payload): void
    // {
    //     // Suy diễn chuẩn hoá input
    //     $event_key = '';
    //     $ctx_data  = [];

    //     if (is_array($payload)) {
    //         $event_key = (string)($payload['event_key'] ?? 'unknown_event');
    //         $ctx_data  = (array)($payload['context'] ?? []);
    //     } elseif (is_object($payload)) {
    //         $event_key = (string)($payload->event_key ?? 'unknown_event');
    //         $ctx_data  = (array)($payload->context ?? []);
    //     }

    //     error_log("[Notif] Dispatcher::handle {$event_key}");

    //     // P0: template cứng (có thể map theo event_key)
    //     $tpl = new TemplateDTO(
    //         subject: 'Sự kiện: {{event_key}}',
    //         body: 'Một sự kiện vừa diễn ra cho công ty #{{company_id}} bởi user #{{actor_id}}.'
    //     );

    //     $ctx = new EventContextDTO(
    //         actor_id: (int)($ctx_data['actor_id'] ?? get_current_user_id()),
    //         company_id: (int)($ctx_data['company_id'] ?? 0)
    //     );

    //     // Preference
    //     if (!$this->preferences->allow($event_key, 'notice') && !$this->preferences->allow($event_key, 'email')) {
    //         error_log("[Notif] Preference blocked for {$event_key}");
    //         return;
    //     }

    //     // Render
    //     $rendered = $this->renderer->render($tpl, [
    //         'event_key'  => $event_key,
    //         'actor_id'   => $ctx->actor_id,
    //         'company_id' => $ctx->company_id,
    //     ]);

    //     // Recipients tối thiểu: actor + admin
    //     $recipients = $this->resolve_recipients_basic($ctx->actor_id);

    //     // Gửi
    //     foreach ($recipients as $user_id) {
    //         foreach (['notice', 'email'] as $channel) {
    //             if (!$this->preferences->allow($event_key, $channel)) {
    //                 continue;
    //             }

    //             $delivery = new DeliveryDTO(
    //                 id: null,
    //                 notification_id: null, // P0: chưa lưu DB
    //                 channel: $channel,
    //                 recipient_id: (int)$user_id,
    //                 status: 'pending',
    //                 created_at: current_time('mysql')
    //             );

    //             $ok = $this->delivery->send($delivery, $rendered);
    //             error_log("[Notif] send {$channel} -> user={$user_id} => " . ($ok ? 'OK' : 'FAIL'));
    //         }
    //     }
    // }

    /** Callback cho EventBus */
    public function handle(array|object $payload): void
    {
        // Chuẩn hoá input
        $event_key = '';
        $ctx_data  = [];

        if (is_array($payload)) {
            $event_key = (string)($payload['event_key'] ?? 'unknown_event');
            $ctx_data  = (array)($payload['context']   ?? []);
        } else {
            $event_key = (string)($payload->event_key ?? 'unknown_event');
            $ctx_data  = (array)($payload->context   ?? []);
        }

        error_log("[Notif] Dispatcher::handle {$event_key}");

        // Build context DTO (thêm delete_reason nếu có)
        $ctx = new EventContextDTO(
            actor_id: (int)($ctx_data['actor_id']   ?? 0),
            company_id: (int)($ctx_data['company_id'] ?? 0)
        );
        $delete_reason = isset($ctx_data['delete_reason']) ? (string)$ctx_data['delete_reason'] : '';

        // Kiểm tra preferences cho 2 kênh mặc định
        if (!$this->preferences->allow($event_key, 'notice') && !$this->preferences->allow($event_key, 'email')) {
            error_log("[Notif] Preference blocked for {$event_key}");
            return;
        }

        // Chọn template theo event
        $tpl = $this->choose_template($event_key);

        // Render với placeholders mở rộng
        $rendered = $this->renderer->render($tpl, [
            'event_key'     => $event_key,
            'actor_id'      => $ctx->actor_id,
            'company_id'    => $ctx->company_id,
            'delete_reason' => $delete_reason,
        ]);

        // Người nhận: actor + admin (P0)
        $recipients = $this->resolve_recipients_basic($ctx->actor_id);

        // Gửi qua các kênh được phép
        foreach ($recipients as $user_id) {
            foreach (['notice', 'email'] as $channel) {
                if (!$this->preferences->allow($event_key, $channel)) {
                    continue;
                }
                $delivery = new DeliveryDTO(
                    id: null,
                    notification_id: null, // P0 chưa lưu DB
                    channel: $channel,
                    recipient_id: (int)$user_id,
                    status: 'pending',
                    created_at: current_time('mysql'),
                );
                $ok = $this->delivery->send($delivery, $rendered);
                error_log("[Notif] send {$channel} -> user={$user_id} => " . ($ok ? 'OK' : 'FAIL'));
            }
        }
    }


    /** Chọn template theo event (P0 hard-code; P1 có thể đọc từ DB/Settings) */
    private function choose_template(string $event_key): TemplateDTO
    {
        return match ($event_key) {
            'CompanySoftDeleted' => new TemplateDTO(
                subject: 'Xoá mềm công ty #{{company_id}}',
                body: 'Công ty #{{company_id}} đã bị xoá mềm bởi user #{{actor_id}}. Lý do: {{delete_reason}}.'
            ),
            'CompanyCreated' => new TemplateDTO(
                subject: 'Tạo công ty mới #{{company_id}}',
                body: 'User #{{actor_id}} vừa tạo công ty #{{company_id}}.'
            ),
            default => new TemplateDTO(
                subject: 'Sự kiện: {{event_key}}',
                body: 'Một sự kiện vừa diễn ra cho công ty #{{company_id}} bởi user #{{actor_id}}.'
            ),
        };
    }

    /** @return int[] */
    private function resolve_recipients_basic(int $actor_id): array
    {
        $ids = [];
        if ($actor_id > 0) {
            $ids[] = $actor_id;
        }
        $admins = get_users(['role' => 'administrator', 'fields' => 'ID']);
        foreach ($admins as $id) {
            $id = (int)$id;
            if (!in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }
}

<?php
declare(strict_types=1);

namespace TMT\CRM\Shared;

use TMT\CRM\Core\Notifications\Domain\DTO\EventContextDTO;

/**
 * EventBus (wrapper) dùng WordPress hooks để publish/subscribe domain events.
 *
 * Mục tiêu:
 * - Cho API thống nhất: EventBus::dispatch() / EventBus::listen().
 * - Dễ nâng cấp: sau này có thể thay bằng Core/Queue mà không sửa nơi phát sự kiện.
 */
final class EventBus
{
    /** Prefix dùng cho tất cả hook sự kiện domain */
    private const HOOK_PREFIX = 'tmt_crm_event_';

    /** Lắng nghe sự kiện domain */
    public static function listen(string $event_key, callable $subscriber, int $priority = 10): void
    {
        $hook = self::to_hook($event_key);
        // Sử dụng add_action của WP. Tham số 1 là EventContextDTO.
        add_action($hook, function ($ctx) use ($subscriber, $event_key) {
            try {
                // Bảo vệ kiểu dữ liệu (tối thiểu)
                if (!$ctx instanceof EventContextDTO) {
                    // Chấp nhận mảng -> chuyển tạm sang DTO
                    if (is_array($ctx)) {
                        $dto = new EventContextDTO();
                        $dto->payload = $ctx['payload'] ?? [];
                        $dto->actor_id = (int)($ctx['actor_id'] ?? 0);
                        $dto->occurred_at = (string)($ctx['occurred_at'] ?? '');
                        $ctx = $dto;
                    } else {
                        return; // bỏ qua input không hợp lệ
                    }
                }
                // Gọi subscriber
                call_user_func($subscriber, $ctx);
            } catch (\Throwable $e) {
                // Không làm chết các subscriber khác; log lỗi nhẹ nhàng
                error_log('[EventBus] Subscriber error for ' . $event_key . ': ' . $e->getMessage());
            }
        }, $priority, 1);
    }

    /** Phát sự kiện domain */
    public static function dispatch(string $event_key, EventContextDTO $ctx): void
    {
        $hook = self::to_hook($event_key);
        /**
         * Lưu ý: do_action sẽ gọi đồng bộ các subscriber. Sau này nếu cần async,
         * có thể chỉnh hàm này ghi vào outbox/queue rồi worker sẽ do_action.
         */
        do_action($hook, $ctx);
    }

    /** Build tên hook WP từ event key */
    private static function to_hook(string $event_key): string
    {
        return self::HOOK_PREFIX . $event_key;
    }
}

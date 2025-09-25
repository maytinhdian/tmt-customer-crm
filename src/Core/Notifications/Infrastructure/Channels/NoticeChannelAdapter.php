<?php

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Channels;

use TMT\CRM\Core\Notifications\Application\Contracts\ChannelAdapterInterface;
use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

final class NoticeChannelAdapter implements ChannelAdapterInterface
{
    /** Key transient theo user */
    private static function transient_key(int $user_id): string
    {
        return 'tmt_crm_notices_' . $user_id;
    }

    /** Gửi = xếp hàng thông điệp vào transient; sẽ in ở admin_notices */
    public function send(DeliveryDTO $delivery, array $rendered): bool
    {
        $user_id = (int)($delivery->recipient_id ?? 0);
        if ($user_id <= 0) {
            return false;
        }

        $subject = (string)($rendered['subject'] ?? '');
        $body    = (string)($rendered['body'] ?? '');

        $queue = get_transient(self::transient_key($user_id));
        $queue = is_array($queue) ? $queue : [];

        $queue[] = [
            'type'    => 'success', // success|info|warning|error (tùy logic)
            'subject' => $subject,
            'body'    => $body,
        ];

        // Giữ 60s là đủ để qua 1 redirect
        set_transient(self::transient_key($user_id), $queue, 60);

        return true;
    }

    /** Gọi ở admin_notices để “in” hàng đợi rồi xóa */
    public static function render_notices(): void
    {
        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return;
        }

        $queue = get_transient(self::transient_key($user_id));
        if (!is_array($queue) || empty($queue)) {
            return;
        }

        delete_transient(self::transient_key($user_id));

        foreach ($queue as $item) {
            $type    = in_array($item['type'], ['success', 'info', 'warning', 'error'], true) ? $item['type'] : 'info';
            $class   = match ($type) {
                'success' => 'notice notice-success is-dismissible',
                'warning' => 'notice notice-warning is-dismissible',
                'error'   => 'notice notice-error is-dismissible',
                default   => 'notice notice-info is-dismissible',
            };

            $subject = esc_html((string)($item['subject'] ?? 'Thông báo'));
            $body    = esc_html((string)($item['body'] ?? ''));

            echo '<div class="' . $class . '"><p><strong>' . $subject . '</strong><br>' . $body . '</p></div>';
        }
    }
}

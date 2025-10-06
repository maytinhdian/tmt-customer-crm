<?php
declare(strict_types=1);

namespace TMT\CRM\Core\Notifications\Infrastructure\Channels;

use TMT\CRM\Core\Notifications\Application\Contracts\ChannelAdapterInterface;
use TMT\CRM\Core\Notifications\Domain\DTO\DeliveryDTO;

/**
 * Kênh hiển thị thông báo trong WordPress Admin (admin_notice).
 * Yêu cầu: AdminNoticeService::boot() đã chạy ở admin_init.
 */
final class AdminNoticeDriver implements ChannelAdapterInterface
{
    /**
     * @param array{subject?:string, body?:string} $rendered
     */
    public function send(DeliveryDTO $delivery, array $rendered): bool
    {
        $subject = trim((string)($rendered['subject'] ?? ''));
        $body    = trim((string)($rendered['body'] ?? ''));

        $msg = $subject !== '' ? $subject . ($body !== '' ? ' — ' . $body : '') : $body;

        // Ưu tiên dùng AdminNoticeService nếu có
        if (class_exists('\TMT\CRM\Shared\Presentation\Support\AdminNoticeService')) {
            \TMT\CRM\Shared\Presentation\Support\AdminNoticeService::success($msg, [
                'dismissible' => true,
                // 'screen' => 'toplevel_page_tmt-crm', // muốn giới hạn 1 screen thì mở dòng này
            ]);
            return true;
        }

        // Fallback: in ra admin_notices (nếu service chưa có/ chưa boot)
        if (function_exists('add_action')) {
            add_action('admin_notices', static function () use ($msg) {
                echo '<div class="notice notice-success is-dismissible"><p>'
                    . esc_html($msg)
                    . '</p></div>';
            });
            return true;
        }

        // Cuối cùng: ghi log để còn lần dấu
        error_log('[AdminNoticeDriver] ' . $msg);
        return false;
    }
}

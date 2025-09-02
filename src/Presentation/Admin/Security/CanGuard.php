<?php
namespace TMT\CRM\Presentation\Admin\Security;

/**
 * CanGuard: helper kiểm tra capability cho Admin (WP).
 * Dùng trong Controller/Screen để đồng bộ cách kiểm tra.
 */
trait CanGuard
{
    /** Kiểm tra quyền, nếu không đủ -> die với thông báo */
    private static function ensure_capability(string $capability, string $message): void
    {
        if (!current_user_can($capability)) {
            // Trả 403 đúng chuẩn WP
            wp_die(
                esc_html($message),
                esc_html__('Không có quyền', 'tmt-crm'),
                ['response' => 403]
            );
        }
    }
}

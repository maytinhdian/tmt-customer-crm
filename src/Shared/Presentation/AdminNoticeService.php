<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Presentation;

final class AdminNoticeService
{
    private const TRANSIENT_PREFIX = 'tmt_crm_notice_';
    private const TTL_SECONDS      = 120;

    /** Khởi tạo hook in notice ở mọi trang WP Admin */
    public static function boot(): void
    {
        add_action('all_admin_notices', [self::class, 'print_notices'], 20);
        error_log('[TMT CRM] AdminNoticeService::boot() is running...');
    }

    /** Thông báo thành công (không ràng buộc screen) */
    public static function success(string $message): void
    {
        self::flash('success', $message, null);
    }

    /** Thông báo lỗi (không ràng buộc screen) */
    public static function error(string $message): void
    {
        self::flash('error', $message, null);
    }

    /** Thông báo thành công cho 1 screen cụ thể */
    public static function success_for_screen(string $screen_id, string $message): void
    {
        $temp_flash = self::flash('success', $message, $screen_id);
        error_log('[AD]' . $screen_id);
    }

    /** Thông báo lỗi cho 1 screen cụ thể */
    public static function error_for_screen(string $screen_id, string $message): void
    {
        self::flash('error', $message, $screen_id);
    }

    /** In ra notice + dọn transient */
    public static function print_notices(): void
    {
        $key   = self::get_key();
        $stash = get_transient($key);
        if (empty($stash) || !is_array($stash)) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $curr_id = $screen ? (string)$screen->id : '';

        $remain = [];
        foreach ($stash as $row) {
            $type      = (string)($row['type'] ?? 'success');
            $message   = (string)($row['message'] ?? '');
            $screen_id = $row['screen_id'] ?? null;

            // Lọc: nếu có screen_id thì chỉ hiển thị ở đúng screen
            $match_screen = (empty($screen_id) || $screen_id === $curr_id);

            if ($match_screen && $message !== '') {
                $class = $type === 'success'
                    ? 'notice notice-success is-dismissible'
                    : 'notice notice-error is-dismissible';

                printf(
                    '<div class="%s"><p>%s</p></div>',
                    esc_attr($class),
                    wp_kses_post($message)
                );
            } else {
                $remain[] = $row;
            }
        }

        if (empty($remain)) {
            delete_transient($key);
        } else {
            set_transient($key, $remain, self::TTL_SECONDS);
        }
    }

    // =======================
    // Internals
    // =======================

    private static function flash(string $type, string $message, ?string $screen_id): void
    {
        $key   = self::get_key();
        $stash = get_transient($key);
        if (!is_array($stash)) {
            $stash = [];
        }

        $stash[] = [
            'type'      => $type,
            'message'   => $message,
            'screen_id' => $screen_id, // null = hiện ở mọi screen
        ];

        set_transient($key, $stash, self::TTL_SECONDS);
    }

    private static function get_key(): string
    {
        $user_id = get_current_user_id();
        return self::TRANSIENT_PREFIX . (int)$user_id;
    }
}

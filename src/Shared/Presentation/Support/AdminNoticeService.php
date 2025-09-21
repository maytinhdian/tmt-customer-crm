<?php

declare(strict_types=1);

namespace TMT\CRM\Shared\Presentation\Support;

final class AdminNoticeService
{
    private const TRANSIENT_PREFIX = 'tmt_crm_notice_';
    private const TTL_SECONDS      = 180; // 3 phút

    /** Gắn vào sớm (plugins_loaded hoặc module::register) */
    public static function boot(): void
    {
        // In theo screen hiện tại (ngoài mọi tab/partial)
        add_action('all_admin_notices', [self::class, 'print_current_screen'], 20);
    }

    /** API ngắn gọn (không ràng buộc screen) */
    public static function success(string $message): void
    {
        self::flash('success', $message, null);
    }
    public static function error(string $message): void
    {
        self::flash('error',   $message, null);
    }
    public static function warning(string $message): void
    {
        self::flash('warning', $message, null);
    }
    public static function info(string $message): void
    {
        self::flash('info',    $message, null);
    }

    /** API theo screen_id (khuyên dùng cho admin-post redirect về screen) */
    public static function success_for_screen(string $screen_id, string $message): void
    {
        self::flash('success', $message, $screen_id);
    }
    public static function error_for_screen(string $screen_id, string $message): void
    {
        self::flash('error',   $message, $screen_id);
    }
    public static function warning_for_screen(string $screen_id, string $message): void
    {
        self::flash('warning', $message, $screen_id);
    }
    public static function info_for_screen(string $screen_id, string $message): void
    {
        self::flash('info',    $message, $screen_id);
    }

    /**
     * In tất cả notice dành cho screen hiện tại (và các notice global).
     * Gắn trên all_admin_notices để đứng trước nội dung tab.
     */
    public static function print_current_screen(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $screen_id = $screen ? (string) $screen->id : '';
        self::print_for_screen($screen_id ?: null); // nếu trống vẫn in notice global
    }

    /**
     * In notice cho 1 screen cụ thể + notice global (screen_id=null).
     * Có thể gọi thủ công trong load-{$hook_suffix} nếu muốn.
     */
    public static function print_for_screen(?string $screen_id): void
    {
        $key   = self::get_key();
        $stash = get_transient($key);
        if (!is_array($stash) || empty($stash)) {
            return;
        }

        $remain = [];
        foreach ($stash as $notice) {
            // notice format: ['type'=>'success|error|warning|info','message'=>'..','screen_id'=>string|null]
            $n_type  = $notice['type']      ?? 'info';
            $n_msg   = (string) ($notice['message']   ?? '');
            $n_sid   = $notice['screen_id'] ?? null;

            // In nếu: (notice global) hoặc (trùng screen)
            $match = ($n_sid === null) || ($screen_id !== null && $n_sid === $screen_id);

            if ($match) {
                $class = match ($n_type) {
                    'success' => 'notice notice-success is-dismissible',
                    'error'   => 'notice notice-error is-dismissible',
                    'warning' => 'notice notice-warning is-dismissible',
                    default   => 'notice notice-info is-dismissible',
                };
                echo '<div class="' . esc_attr($class) . '"><p>' . wp_kses_post($n_msg) . '</p></div>';
            } else {
                // Giữ lại để in ở screen khác
                $remain[] = $notice;
            }
        }

        if ($remain) {
            set_transient($key, $remain, self::TTL_SECONDS);
        } else {
            delete_transient($key);
        }
    }

    /** Lưu notice vào transient theo user */
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
            'screen_id' => $screen_id, // null = global
        ];
        set_transient($key, $stash, self::TTL_SECONDS);
    }

    private static function get_key(): string
    {
        $user_id = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
        return self::TRANSIENT_PREFIX . $user_id;
    }
}

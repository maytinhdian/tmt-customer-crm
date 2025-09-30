<?php

/**
 * NotificationsModule (file chính)
 * Entry point khởi động module Notifications
 */

declare(strict_types=1);

namespace TMT\CRM\Core\Notifications;

use TMT\CRM\Core\Notifications\Infrastructure\Providers\NotificationsServiceProvider;

final class NotificationsModule
{
    /** Khởi động module: bind container, đăng ký hooks, subscribe events */
    public static function register(): void
    {
        NotificationsServiceProvider::register();

        add_action('init', [self::class, 'on_init'], 1);
        add_action('admin_init', [self::class, 'on_admin_init'], 1);
    }

    public static function on_init(): void
    {
        // Chỗ cho routes/ajax nếu cần
    }

    public static function on_admin_init(): void
    {
        // // (Tuỳ chọn) Test nhanh pipeline bằng admin_notices
        // add_action('admin_notices', function () {
        //     echo '<div class="notice notice-info"><p>TMT NotificationsModule đã khởi động.</p></div>';
        // });
        // // Test thủ công
        // add_action('admin_init', function () {
        //     do_action('tmt_crm_debug_notify', 'CompanyCreated', [
        //         'actor_id' => get_current_user_id(),
        //         'subject'  => 'Tạo công ty thành công',
        //         'body'     => 'Công ty A vừa được tạo bởi bạn.',
        //     ], ['notice','email']); // đổi kênh tuỳ ý
        // });
    }
}

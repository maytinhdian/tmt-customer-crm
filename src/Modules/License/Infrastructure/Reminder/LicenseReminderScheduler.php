<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Infrastructure\Reminder;

use TMT\CRM\Modules\License\Application\Services\ReminderService;
use TMT\CRM\Modules\License\Infrastructure\Persistence\WpdbCredentialRepository;

final class LicenseReminderScheduler
{
    public const HOOK = 'tmt_crm_license_daily_reminder';

    public static function register(): void
    {
        // Lịch hằng ngày
        add_action('init', [self::class, 'ensure_scheduled']);
        // Handler
        add_action(self::HOOK, [self::class, 'run_daily']);
    }

    public static function ensure_scheduled(): void
    {
        if (!wp_next_scheduled(self::HOOK)) {
            // chạy 2:00 sáng theo timezone WP
            $first = strtotime('tomorrow 2am');
            wp_schedule_event($first ?: (time() + DAY_IN_SECONDS), 'daily', self::HOOK);
        }
    }

    public static function run_daily(): void
    {
        // Lấy cấu hình số ngày
        $days = (int) get_option('tmt_crm_license_expiring_days', 14);
        $days = $days > 0 ? $days : 14;

        global $wpdb;
        $repo = new WpdbCredentialRepository($wpdb);
        $svc  = new ReminderService($repo);

        $result = $svc->find_expiring_within_days($days, 1, 200); // tối đa 200 item/ngày
        // Lưu cache tạm để admin page/notice đọc
        set_transient('tmt_crm_license_expiring_cache', [
            'at'    => current_time('mysql'),
            'days'  => $days,
            'total' => (int)($result['total'] ?? 0),
            'items' => $result['items'] ?? [],
        ], 12 * HOUR_IN_SECONDS);
    }
}

<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\Core\Records\Presentation\Cron;

use TMT\CRM\Shared\Container\Container;

final class RegisterCron
{
    public const HOOK_DAILY = 'tmt_crm_records_retention_daily';

    public static function register(): void
    {
        if (!wp_next_scheduled(self::HOOK_DAILY)) {
            wp_schedule_event(time() + 60, 'daily', self::HOOK_DAILY);
        }

        add_action(self::HOOK_DAILY, [self::class, 'handle_daily']);
    }

    public static function handle_daily(): void
    {
        $settings = get_option('tmt_crm_records_settings', []);
        $retention_days = (int)($settings['retention_days'] ?? 180);
        $soft_delete_expire = (int)($settings['soft_delete_expire'] ?? 0);

        /** @var \TMT\CRM\Modules\Core\Records\Application\Services\RetentionService $svc */
        $svc = Container::get('core.records.retention_service');

        // Dọn archive cũ
        $svc->purge_archives_expired($retention_days);

        // Nếu muốn auto xoá mềm quá hạn — KHÔNG khuyến nghị bật mặc định.
        if ($soft_delete_expire > 0) {
            // Ví dụ: $svc->purge_soft_deleted_older_than($wpdb->prefix.'crm_companies', $soft_delete_expire);
        }
    }
}

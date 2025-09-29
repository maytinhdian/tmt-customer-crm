<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Notice;

final class LicenseReminderAdminNotice
{
    public static function register(): void
    {
        add_action('admin_notices', [self::class, 'render']);
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) return;

        $enabled = (int) get_option('tmt_crm_license_notice_enabled', 1);
        if ($enabled !== 1) return;

        $cache = get_transient('tmt_crm_license_expiring_cache');
        $days  = (int) get_option('tmt_crm_license_expiring_days', 14);

        // Nếu chưa có cache (cron chưa chạy), bỏ qua để tránh ồn
        if (!is_array($cache)) return;

        $total = (int)($cache['total'] ?? 0);
        if ($total <= 0) return;

        $url = add_query_arg(['page' => 'tmt-crm-licenses-edit', 'tab' => 'deliveries'], admin_url('admin.php'));
        $exp_url = add_query_arg(['page' => 'tmt-crm-licenses-expiring'], admin_url('admin.php'));
?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <?php
                printf(
                    esc_html__('Có %d license sẽ hết hạn trong %d ngày tới. %s', 'tmt-crm'),
                    (int)$total,
                    (int)$days,
                    sprintf('<a href="%s">%s</a>', esc_url($exp_url), esc_html__('Xem danh sách', 'tmt-crm'))
                );
                ?>
            </p>
        </div>
<?php
    }
}

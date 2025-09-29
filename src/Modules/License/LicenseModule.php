<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License;

use TMT\CRM\Modules\License\Presentation\Admin\Controller\{LicenseController, LicenseAllocationController, LicenseActivationController, LicenseDeliveryController, LicenseSecretController};
use TMT\CRM\Modules\License\Presentation\Admin\Menu\LicenseMenu;
use TMT\CRM\Modules\License\Infrastructure\Providers\LicenseServiceProvider;
use TMT\CRM\Modules\License\Infrastructure\Reminder\LicenseReminderScheduler;
use TMT\CRM\Modules\License\Presentation\Admin\Notice\LicenseReminderAdminNotice;
use TMT\CRM\Modules\License\Presentation\Admin\Screen\LicenseSettingsScreen;

use TMT\CRM\Core\Settings\SettingsRegistry;
use TMT\CRM\Modules\License\Presentation\Admin\Settings\LicenseSettingsSection;

/**
 * LicenseModule.php (file chính)
 * - Đăng ký installer, menu, assets, routes, service providers.
 * - Chỉ chứa wiring/bootstrapping, không chứa nghiệp vụ.
 */
final class LicenseModule
{
    /**
     * Gọi 1 lần từ plugin chính (tmt-customer-crm.php):
     *
     * add_action('plugins_loaded', [\TMT\CRM\Modules\License\LicenseModule::class, 'register'], 1);
     */
    public static function register(): void
    {


        // 2) Đăng ký service vào DI Container khi Container boot
        LicenseServiceProvider::register();
        LicenseAllocationController::register();
        LicenseActivationController::register();
        LicenseDeliveryController::register();

        // 3) Menu + Screen
        add_action('admin_menu', [LicenseMenu::class, 'register'], 20);

        // 4) Controller (xử lý reveal/save/…)
        LicenseController::register();
        LicenseSecretController::register();

        // 5) Enqueue CSS/JS cho admin
        add_action('admin_enqueue_scripts', function ($hook) {
            if (strpos($hook, 'tmt-crm-licenses') !== false) {
                // wp_enqueue_script('tmt-license-secret', plugins_url('../../assets/js/license-secret.js'), ['jquery'], '1.0', true);
                wp_enqueue_script(
                    'tmt-license-secret',
                    plugins_url('assets/admin/js/license-secret.js', TMT_CRM_FILE),
                    ['jquery'],
                    '1.0',
                    true
                );
                wp_localize_script('tmt-license-secret', 'TMTCRM_LicenseSecret', [
                    'nonce' => wp_create_nonce('tmt_crm_license_reveal_secret_'),
                ]);
            }
        });

        // LicenseAssets::register();
        // Settings API
        // LicenseSettingsScreen::register();

        // 1) Add section vào Registry
        SettingsRegistry::add_section(new LicenseSettingsSection());
        
        // Cron + Notices
        LicenseReminderScheduler::register();
        LicenseReminderAdminNotice::register();
    }
}

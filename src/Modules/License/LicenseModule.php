<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License;

use TMT\CRM\Modules\License\Presentation\Admin\Controller\LicenseController;
use TMT\CRM\Modules\License\Presentation\Admin\Menu\LicenseMenu;

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

        // PasswordServiceProvider::register();

        // 3) Menu + Screen
        add_action('admin_menu', [LicenseMenu::class, 'register'], 20);

        // 4) Controller (xử lý reveal/save/…)
        LicenseController::register();

        // 5) Enqueue CSS/JS cho admin
        // LicenseAssets::register();
    }
}

<?php
declare(strict_types=1);

namespace TMT\CRM\Modules\License;

use TMT\CRM\Modules\License\Installer\LicenseInstaller;
use TMT\CRM\Modules\License\Presentation\Admin\Menu\LicenseMenu;

/**
 * LicenseModule.php (file chính)
 * - Đăng ký installer, menu, assets, routes, service providers.
 * - Chỉ chứa wiring/bootstrapping, không chứa nghiệp vụ.
 */
final class LicenseModule
{
    public static function register(): void
    {
        // Installer
        add_action('plugins_loaded', [LicenseInstaller::class, 'maybe_install'], 5);

        // Admin Menu
        add_action('admin_menu', [LicenseMenu::class, 'register'], 20);
    }
}

<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Modules\Password\Installer\Installer as PasswordInstaller;
use TMT\CRM\Modules\Password\Infrastructure\Providers\PasswordServiceProvider;
use TMT\CRM\Modules\Password\Presentation\Admin\Menu\PasswordMenu;
use TMT\CRM\Modules\Password\Presentation\Admin\Controller\PasswordController;
use TMT\CRM\Modules\Password\Application\Services\PasswordService;
use TMT\CRM\Modules\Password\Presentation\Admin\Assets\PasswordAssets;

/**
 * PasswordModule
 *
 * Bootstrap (file chính) của module Password.
 * - Gắn Installer để đảm bảo DB schema đúng phiên bản.
 * - Đăng ký service vào Container qua hook 'tmt_crm_container_boot'.
 * - Đăng ký admin menu & controller.
 */
final class PasswordModule
{
    /**
     * Gọi 1 lần từ plugin chính (tmt-customer-crm.php):
     *
     * add_action('plugins_loaded', [\TMT\CRM\Modules\Password\PasswordModule::class, 'register'], 1);
     */
    public static function register(): void
    {


        // 2) Đăng ký service vào DI Container khi Container boot

        PasswordServiceProvider::register();

        // 3) Menu + Screen
        add_action('admin_menu', [PasswordMenu::class, 'register'], 22);

        // 4) Controller (xử lý reveal/save/…)
        PasswordController::register();

        // 5) Enqueue CSS/JS cho admin
        PasswordAssets::register();
    }
}

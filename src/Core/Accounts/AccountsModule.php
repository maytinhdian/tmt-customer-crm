<?php
// bootstrap (file chính)
declare(strict_types=1);

namespace TMT\CRM\Core\Accounts;

use TMT\CRM\Core\Accounts\Infrastructure\Providers\AccountsServiceProvider;
use TMT\CRM\Core\Accounts\Presentation\Admin\Ajax\UserAjaxController;
use TMT\CRM\Core\Accounts\Presentation\Admin\Settings\AccountSettingIntegration;

final class AccountsModule
{
    public static function bootstrap(): void // dùng từ bootstrap (file chính)
    {
        AccountsServiceProvider::register();


        // Đăng ký AJAX picker dùng chung
        add_action('init', [UserAjaxController::class, 'bootstrap']);

        // 3) Tích hợp vào Core/Settings (Section "User References")
        AccountSettingIntegration::register();
    }
}

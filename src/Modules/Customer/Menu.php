<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer;

use TMT\CRM\Modules\Customer\Presentation\Admin\Screen\CustomerScreen;
use TMT\CRM\Modules\Customer\Presentation\Admin\Controller\CustomerController;

final class Menu
{
    /** Hook id của màn hình Customers */
    private static string $hook_suffix = '';

    /** Gọi 1 lần ở bootstrap (file chính) */
    public static function register(): void
    {
        // 1) Menu + Screen Options
        add_action('admin_menu', [self::class, 'register_admin_menu'], 20);

        // 2) Controller: admin-post actions (đăng ký luôn tại đây)
        CustomerController::register();
    }

    /** Tạo submenu & bàn giao hook_suffix cho Screen */
    public static function register_admin_menu(): void
    {
        self::$hook_suffix = add_submenu_page(
            'tmt-crm',                              // menu cha CRM
            __('Liên hệ - Khách hàng', 'tmt-crm'),           // <title>
            __('Liên hệ - Khách hàng', 'tmt-crm'),           // label menu
            'manage_options',                      // TODO: thay bằng Capability::CUSTOMER_READ nếu bạn có
            CustomerScreen::PAGE_SLUG,             // slug
            [CustomerScreen::class, 'dispatch'],
            20
        ) ?: '';

        if (self::$hook_suffix !== '') {
            CustomerScreen::set_hook_suffix(self::$hook_suffix);

            // Nếu có Screen Options -> đăng ký ở đây
            add_action('load-' . self::$hook_suffix, [CustomerScreen::class, 'on_load_customers']);
            add_filter('set-screen-option', [CustomerScreen::class, 'save_screen_option'], 10, 3);
        }
    }
}

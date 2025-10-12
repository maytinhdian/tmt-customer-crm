<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Company;

use TMT\CRM\Modules\Company\Presentation\Admin\Screen\CompanyScreen;
use TMT\CRM\Modules\Company\Presentation\Admin\Controller\CompanyController;

final class Menu
{
    /** Hook id của màn hình Companys */
    private static string $hook_suffix = '';

    /** Gọi 1 lần ở bootstrap (file chính) */
    public static function register(): void
    {
        // 1) Menu + Screen Options
        add_action('admin_menu', [self::class, 'register_admin_menu'], 20);

    }

    /** Tạo submenu & bàn giao hook_suffix cho Screen */
    public static function register_admin_menu(): void
    {
        self::$hook_suffix = add_submenu_page(
            'tmt-crm',                              // menu cha CRM
            __('Công ty', 'tmt-crm'),           // <title>
            __('Công ty', 'tmt-crm'),           // label menu
            'manage_options',                      // TODO: thay bằng Capability::CUSTOMER_READ nếu bạn có
            CompanyScreen::PAGE_SLUG,             // slug
            [CompanyScreen::class, 'dispatch'],
            20
        ) ?: '';

        if (self::$hook_suffix !== '') {
            CompanyScreen::set_hook_suffix(self::$hook_suffix);

            // Nếu có Screen Options -> đăng ký ở đây
            add_action('load-' . self::$hook_suffix, [CompanyScreen::class, 'on_load_companies']);
            add_filter('set-screen-option', [CompanyScreen::class, 'save_screen_option'], 10, 3);
        }
    }
}

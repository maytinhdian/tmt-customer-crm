<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation;

use TMT\CRM\Modules\Quotation\Presentation\Admin\Screen\QuoteScreen;
use TMT\CRM\Modules\Quotation\Presentation\Admin\Controller\QuoteController;

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
        QuoteController::register();
    }

    /** Tạo submenu & bàn giao hook_suffix cho Screen */
    public static function register_admin_menu(): void
    {
        self::$hook_suffix = add_submenu_page(
            'tmt-crm',                              // menu cha CRM
            __('Báo giá - Đơn hàng', 'tmt-crm'),           // <title>
            __('Báo giá - Đơn hàng', 'tmt-crm'),           // label menu
            'manage_options',                      // TODO: thay bằng Capability::CUSTOMER_READ nếu bạn có
            QuoteScreen::PAGE_SLUG,             // slug
            [QuoteScreen::class, 'dispatch'],
            20
        ) ?: '';

        if (self::$hook_suffix !== '') {
            QuoteScreen::set_hook_suffix(self::$hook_suffix);

            // Nếu có Screen Options -> đăng ký ở đây
            add_action('load-' . self::$hook_suffix, [QuoteScreen::class, 'on_load_quotes']);
            add_filter('set-screen-option', [QuoteScreen::class, 'save_screen_option'], 10, 3);
        }
    }
}

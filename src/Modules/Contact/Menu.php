<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Contact;

use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Modules\Contact\Presentation\Admin\Controller\CompanyContactController;
use TMT\CRM\Modules\Contact\Presentation\Admin\Screen\{CompanyContactsScreen};

final class Menu
{
    /** Hook id của màn hình Contact */
    private static string $hook_suffix = '';

    /** Gọi 1 lần ở bootstrap (file chính) */
    public static function register(): void
    {
        // 1) Menu + Screen Options
        add_action('admin_menu', [self::class, 'register_admin_menu'], 20);

        // 2) Controller: admin-post actions (đăng ký luôn tại đây)
        CompanyContactController::register();
    }

    /** Tạo submenu & bàn giao hook_suffix cho Screen */
    public static function register_admin_menu(): void
    {
        self::$hook_suffix = add_submenu_page(
            'tmt-crm',                              // menu cha CRM
            __('Quản lý liên hệ công ty', 'tmt-crm'),           // <title>
            __('Quản lý liên hệ công ty', 'tmt-crm'),           // label menu
            'manage_options',                      // TODO: thay bằng Capability::CUSTOMER_READ nếu bạn có
            CompanyContactsScreen::PAGE_SLUG,             // slug
            [CompanyContactsScreen::class, 'dispatch'],
            20
        ) ?: '';

        if (self::$hook_suffix !== '') {
            CompanyContactsScreen::set_hook_suffix(self::$hook_suffix);

            // Nếu có Screen Options -> đăng ký ở đây
            add_action('load-' . self::$hook_suffix, [CompanyContactsScreen::class, 'on_load_contacts']);
            add_filter('set-screen-option', [CompanyContactsScreen::class, 'save_screen_option'], 10, 3);
        }
        // Ẩn khỏi menu
        // remove_submenu_page('tmt-crm', 'tmt-crm-company-contacts');
        add_action('admin_head', function () {
            remove_submenu_page('tmt-crm', CompanyContactsScreen::PAGE_SLUG);
        });
    }
}

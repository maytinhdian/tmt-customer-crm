<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Note;

use TMT\CRM\Modules\Note\Presentation\Admin\Screen\CompanyNotesFilesScreen;
use TMT\CRM\Modules\Note\Presentation\Admin\Screen\CustomerNotesFilesScreen;
use TMT\CRM\Modules\Note\Presentation\Admin\Controller\NotesFilesController;

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
        NotesFilesController::register();
    }

    /** Tạo submenu & bàn giao hook_suffix cho Screen */
    public static function register_admin_menu(): void
    {
        self::$hook_suffix = add_submenu_page(
            'tmt-crm',                              // menu cha CRM
            __('Ghi chú - Khách hàng', 'tmt-crm'),           // <title>
            __('Ghi chú - Khách hàng', 'tmt-crm'),           // label menu
            'manage_options',                      // TODO: thay bằng Capability::CUSTOMER_READ nếu bạn có
            CompanyNotesFilesScreen::PAGE_SLUG,             // slug
            [CompanyNotesFilesScreen::class, 'dispatch'],
            20
        ) ?: '';

        // Ẩn khỏi menu
        // remove_submenu_page('tmt-crm', 'tmt-crm-company-contacts');
        add_action('admin_head', function () {
            remove_submenu_page('tmt-crm', CompanyNotesFilesScreen::PAGE_SLUG);
        });

        if (self::$hook_suffix !== '') {
            // CompanyNotesFilesScreen::set_hook_suffix(self::$hook_suffix);

            // Nếu có Screen Options -> đăng ký ở đây
            add_action('load-' . self::$hook_suffix, [CompanyNotesFilesScreen::class, 'on_load_notes']);
            add_filter('set-screen-option', [CompanyNotesFilesScreen::class, 'save_screen_option'], 10, 3);
        }
    }
}

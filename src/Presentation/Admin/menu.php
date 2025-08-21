<?php
// namespace TMT\CRM\Presentation\Admin;

// final class Menu {
//     public static function register(): void {
//         add_menu_page(
//             __('CRM', 'tmt-customer-crm'),
//             __('CRM', 'tmt-customer-crm'),
//             'manage_options',
//             'tmt-crm',
//             [DashboardScreen::class, 'render'],
//             'dashicons-groups',
//             56
//         );
//         // add_submenu_page('tmt-crm', __('Customers','tmt-customer-crm'), __('Customers','tmt-customer-crm'), 'manage_options', 'tmt-crm-customers', [CustomerScreen::class, 'boot']);
//         // add_submenu_page('tmt-crm', __('Add/Edit Customer','tmt-customer-crm'), __('Add/Edit Customer','tmt-customer-crm'), 'manage_options', 'tmt-crm-customer-edit', [CustomerEditScreen::class, 'renderPage']);
//     }
// }


// <?php
namespace TMT\CRM\Presentation\Admin;

defined('ABSPATH') || exit;

/**
 * Gom toàn bộ menu/submenu của plugin về một nơi.
 * Chỉ giữ 1 submenu "Customers". Mọi màn khác thêm sau tại đây.
 */
final class Menu
{
    public static function register(): void
    {
        // Menu cha CRM
        add_menu_page(
            __('CRM', 'tmt-crm'),
            __('CRM', 'tmt-crm'),
            'manage_options',
            'tmt-crm',
            [self::class, 'render_dashboard'], // Có thể thay bằng DashboardScreen::render
            'dashicons-groups',
            25
        );

        // Xoá submenu tự sinh trùng tên "CRM"
        remove_submenu_page('tmt-crm', 'tmt-crm');

        // Submenu duy nhất: Customers
        add_submenu_page(
            'tmt-crm',
            __('Customers', 'tmt-crm'),
            __('Customers', 'tmt-crm'),
            'manage_options',
            'tmt-crm-customers',
            [CustomerScreen::class, 'dispatch'] // Bộ định tuyến view list/form theo action
        );
    }

    /** Dashboard placeholder (tuỳ bạn làm sau) */
    public static function render_dashboard(): void
    {
        echo '<div class="wrap"><h1>CRM</h1><p>Chào mừng đến CRM.</p></div>';
    }
}

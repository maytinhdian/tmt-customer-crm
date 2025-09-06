<?php

namespace TMT\CRM\Presentation\Admin;

use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Presentation\Admin\Screen\{CustomerScreen, CompanyScreen, QuoteScreen, CompanyContactsScreen};

defined('ABSPATH') || exit;

final class Menu
{

    /** Hook id của màn hình Customers */
    private static string $customers_hook = '';

    /** Hook id của màn hình Companies (mới) */
    private static string $companies_hook = '';

    /** Hook id của màn hình Quotes (mới) */
    private static string $quotes_hook = '';

    /** Hook id của màn hình Contacts (mới) */
    private static string $contacts_hook = '';

    public static function register(): void
    {
        add_menu_page(
            __('CRM', 'tmt-crm'),
            __('CRM', 'tmt-crm'),
            Capability::CUSTOMER_READ,               // Cha & con cùng cap
            'tmt-crm',
            [self::class, 'render_dashboard'],
            'dashicons-groups',
            25
        );

        remove_submenu_page('tmt-crm', 'tmt-crm');

        self::$customers_hook = add_submenu_page(
            'tmt-crm',
            __('Customers', 'tmt-crm'),
            __('Customers', 'tmt-crm'),
            Capability::CUSTOMER_READ,
            'tmt-crm-customers',
            [CustomerScreen::class, 'dispatch']
        );

        // 👉 Bàn giao hook_suffix lại cho CustomerScreen
        CustomerScreen::set_hook_suffix(self::$customers_hook);

        // Screen Options cho Customers
        add_action('load-' . self::$customers_hook, [CustomerScreen::class, 'on_load_customers']);
        add_filter('set-screen-option', [CustomerScreen::class, 'save_screen_option'], 10, 3);

        // //-- Submenu: Companies (MỚI) ---
        self::$companies_hook = add_submenu_page(
            'tmt-crm', // slug của menu cha
            __('Companies Test', 'tmt-crm'), // tiêu đề hiển thị trên <title>
            __('Companies', 'tmt-crm'), // tiêu đề hiển thị trong menu
            Capability::COMPANY_READ, // quyền (capability) để xem menu này
            'tmt-crm-companies',  // slug của trang 
            [CompanyScreen::class, 'dispatch'] // hàm/class method render nội dung
        );

        // Screen Options cho Companies
        add_action('load-' . self::$companies_hook, [CompanyScreen::class, 'on_load_companies']);
        add_filter('set-screen-option', [CompanyScreen::class, 'save_screen_option'], 10, 3);

        // ===== Báo giá / Đơn hàng / Hoá đơn =====
        self::$quotes_hook = add_submenu_page(
            'tmt-crm',
            __('Quotations Test', 'tmt-crm'),
            __('Quotations', 'tmt-crm'),
            Capability::QUOTE_READ,
            'tmt-crm-quotes',
            [QuoteScreen::class, 'dispatch']
        );

        // Screen Options cho Quotes 
        add_action('load-' . self::$contacts_hook, [QuoteScreen::class, 'on_load_quotes']);


        self::$contacts_hook = add_submenu_page(
            'tmt-crm',
            __('Contacts Test', 'tmt-crm'),
            __('Contacts', 'tmt-crm'),
            Capability::COMPANY_READ,
            'tmt-crm-company-contacts',
            [CompanyContactsScreen::class, 'dispatch']
        );
        // 👉 Bàn giao hook_suffix lại cho CustomerScreen
        CompanyContactsScreen::set_hook_suffix(self::$contacts_hook);

        // Ẩn khỏi menu
        // remove_submenu_page('tmt-crm', 'tmt-crm-company-contacts');
        add_action('admin_head', function () {
            remove_submenu_page('tmt-crm', CompanyContactsScreen::PAGE_SLUG);
        });

        // Screen Options cho CompanyContactScreen 
        add_action('load-' . self::$contacts_hook, [CompanyContactsScreen::class, 'on_load_contacts']);

        // // (Tuỳ chọn) log screen id để chắc ID khớp
        add_action('current_screen', function ($s) {
            error_log('SCREEN: ' . $s->id);
        });
    }

    public static function render_dashboard(): void
    {
        echo '<div class="wrap"><h1>CRM</h1><p>' . esc_html__('Chào mừng đến CRM.', 'tmt-crm') . '</p></div>';
    }
}

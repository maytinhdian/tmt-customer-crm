<?php
namespace TMT\CRM\Presentation\Admin;

use TMT\CRM\Infrastructure\Security\Capability;

defined('ABSPATH') || exit;

final class Menu
{
    private static string $customers_hook = '';

    public static function register(): void
    {
        add_menu_page(
            __('CRM', 'tmt-crm'),
            __('CRM', 'tmt-crm'),
            Capability::MANAGE,               // Cha & con cùng cap
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
            Capability::MANAGE,
            'tmt-crm-customers',
            [\TMT\CRM\Presentation\Admin\CustomerScreen::class, 'dispatch']
        );

        // Screen Options (per-page)
        add_action('load-' . self::$customers_hook, [\TMT\CRM\Presentation\Admin\CustomerScreen::class, 'on_load_customers']);
        add_filter('set-screen-option', [\TMT\CRM\Presentation\Admin\CustomerScreen::class, 'save_screen_option'], 10, 3);
    }

    public static function render_dashboard(): void
    {
        echo '<div class="wrap"><h1>CRM</h1><p>' . esc_html__('Chào mừng đến CRM.', 'tmt-crm') . '</p></div>';
    }
}

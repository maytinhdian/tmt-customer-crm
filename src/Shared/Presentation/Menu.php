<?php

namespace TMT\CRM\Shared\Presentation;

use TMT\CRM\Core\Capabilities\Domain\Capability;

defined('ABSPATH') || exit;

final class Menu
{


    public static function register(): void
    {
        add_menu_page(
            __('CRM', 'tmt-crm'),
            __('CRM', 'tmt-crm'),
            Capability::CUSTOMER_READ,             // Cha & con cùng cap
            'tmt-crm',
            [self::class, 'render_dashboard'],
            'dashicons-groups',
            56
        );


        remove_submenu_page('tmt-crm', 'tmt-crm');

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

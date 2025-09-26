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

        add_action('admin_menu', function () {
            $parent = 'tmt-crm'; // slug menu CRM
            $order  = [
                'tmt-crm',             // CRM (mục dashboard của menu cha)
                'tmt-crm-contacts',    // Liên hệ – Khách hàng
                'tmt-crm-quotes',      // Báo giá – Đơn hàng
                'tmt-crm-companies',   // Công ty
                'tmt-crm-passwords',   // Quản lý mật khẩu
                'tmt-crm-settings',    // Cài đặt
            ];

            global $submenu;
            if (empty($submenu[$parent]) || !is_array($submenu[$parent])) {
                return;
            }

            // Sắp xếp theo mảng $order
            usort($submenu[$parent], function ($a, $b) use ($order) {
                // $a[2] và $b[2] là menu_slug
                $ai = array_search($a[2], $order, true);
                $bi = array_search($b[2], $order, true);
                $ai = ($ai === false) ? PHP_INT_MAX : $ai;
                $bi = ($bi === false) ? PHP_INT_MAX : $bi;
                return $ai <=> $bi;
            });
        }, 99);
    }

    public static function render_dashboard(): void
    {
        echo '<div class="wrap"><h1>CRM</h1><p>' . esc_html__('Chào mừng đến CRM.', 'tmt-crm') . '</p></div>';
    }
}

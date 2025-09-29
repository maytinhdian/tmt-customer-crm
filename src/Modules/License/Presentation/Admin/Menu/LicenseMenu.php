<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Menu;

use TMT\CRM\Modules\License\Presentation\Admin\Screen\LicenseScreen;
use TMT\CRM\Modules\License\Presentation\Admin\Screen\ExpiringSoonScreen;
use TMT\CRM\Modules\License\Presentation\Admin\Screen\LicenseSettingsScreen;

final class LicenseMenu
{
    public static function register(): void
    {
        // add_menu_page(
        //     page_title: __('Licenses', 'tmt-crm'),
        //     menu_title: __('Licenses', 'tmt-crm'),
        //     capability: 'manage_options',
        //     menu_slug: 'tmt-crm-licenses',
        //     callback: [LicenseScreen::class, 'render_list'],
        //     icon_url: 'dashicons-admin-network',
        //     position: 56
        // );

        add_submenu_page(
            parent_slug: 'tmt-crm',
            page_title: __('License', 'tmt-crm'),
            menu_title: __('License', 'tmt-crm'),
            capability: 'manage_options',
            menu_slug: 'tmt-crm-licenses',
            callback: [LicenseScreen::class, 'render_list']
        );
        add_submenu_page(
            parent_slug: 'tmt-crm',
            page_title: __('Add / Edit License', 'tmt-crm'),
            menu_title: __('Add New', 'tmt-crm'),
            capability: 'manage_options',
            menu_slug: 'tmt-crm-licenses-edit',
            callback: [LicenseScreen::class, 'render_form']
        );
        // trong LicenseMenu::register():
        add_submenu_page(
            parent_slug: 'tmt-crm',
            page_title: __('Expiring Soon', 'tmt-crm'),
            menu_title: __('Expiring Soon', 'tmt-crm'),
            capability: 'manage_options',
            menu_slug: 'tmt-crm-licenses-expiring',
            callback: [ExpiringSoonScreen::class, 'render']
        );

        // Ẩn khỏi menu
        // remove_submenu_page('tmt-crm', 'tmt-crm-company-contacts');
        add_action('admin_head', function () {
            remove_submenu_page('tmt-crm', 'tmt-crm-licenses-edit');
            remove_submenu_page('tmt-crm', 'tmt-crm-licenses-expiring');
        });

        // add_submenu_page(
        //     parent_slug: 'tmt-crm',
        //     page_title: __('Settings', 'tmt-crm'),
        //     menu_title: __('Settings', 'tmt-crm'),
        //     capability: 'manage_options',
        //     menu_slug: 'tmt-crm-licenses-settings',
        //     callback: [LicenseSettingsScreen::class, 'render']
        // );
    }
}

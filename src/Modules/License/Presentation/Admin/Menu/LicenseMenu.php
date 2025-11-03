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
        // ví dụ trong Menu/Module bootstrap ( file chính )

        add_submenu_page(
            'tmt-crm',                          // parent slug của CRM
            __('Licenses', 'tmt-crm'),
            __('Licenses', 'tmt-crm'),
            'manage_options',
            LicenseScreen::PAGE_SLUG,
            [LicenseScreen::class, 'route']
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
    }
}

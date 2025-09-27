<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\Menu;

use TMT\CRM\Modules\License\Presentation\Admin\Screen\LicenseScreen;

final class LicenseMenu
{
    public static function register(): void
    {
        add_menu_page(
            page_title: __('Licenses', 'tmt-crm'),
            menu_title: __('Licenses', 'tmt-crm'),
            capability: 'manage_options',
            menu_slug: 'tmt-crm-licenses',
            callback: [LicenseScreen::class, 'render_list'],
            icon_url: 'dashicons-admin-network',
            position: 56
        );

        add_submenu_page(
            parent_slug: 'tmt-crm-licenses',
            page_title: __('Add / Edit License', 'tmt-crm'),
            menu_title: __('Add New', 'tmt-crm'),
            capability: 'manage_options',
            menu_slug: 'tmt-crm-licenses-edit',
            callback: [LicenseScreen::class, 'render_form']
        );
    }
}

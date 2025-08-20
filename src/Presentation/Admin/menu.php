<?php
namespace TMT\CRM\Presentation\Admin;

final class Menu {
    public static function register(): void {
        add_menu_page(
            __('CRM', 'tmt-customer-crm'),
            __('CRM', 'tmt-customer-crm'),
            'manage_options',
            'tmt-crm',
            [Dashboard_Screen::class, 'render'],
            'dashicons-groups',
            56
        );
        add_submenu_page('tmt-crm', __('Customers','tmt-customer-crm'), __('Customers','tmt-customer-crm'), 'manage_options', 'tmt-crm-customers', [Customers_Screen::class, 'render']);
        add_submenu_page('tmt-crm', __('Add/Edit Customer','tmt-customer-crm'), __('Add/Edit Customer','tmt-customer-crm'), 'manage_options', 'tmt-crm-customer-edit', [Customer_Edit_Screen::class, 'render']);
    }
}

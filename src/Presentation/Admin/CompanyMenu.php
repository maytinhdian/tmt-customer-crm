<?php

namespace TMT\CRM\Presentation\Admin;


/**
 * Đăng ký submenu cho Company dưới menu cha tmt-crm (đã có).
 */
class CompanyMenu
{
    public static function register(): void
    {
        add_submenu_page(
            'tmt-crm', // parent slug (menu cha đã có)
            'Công ty', // page title
            'Công ty', // menu title
            'manage_tmt_crm_companies', // capability
            'tmt-crm-companies', // menu slug
            [CompanyScreen::class, 'render'] // callback
        );
    }
}

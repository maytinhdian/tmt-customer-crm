<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Presentation\Admin\Menu;

use TMT\CRM\Modules\Password\Presentation\Admin\Screen\PasswordScreen;

final class PasswordMenu
{
    public static function register(): void
    {
        add_submenu_page(
            'tmt-crm',
            __('Quản lý mật khẩu', 'tmt-crm'),
            __('Quản lý mật khẩu', 'tmt-crm'),
            'manage_options',
            PasswordScreen::PAGE_SLUG,
            [PasswordScreen::class, 'render'],
            'dashicons-lock',
        );
    }
}

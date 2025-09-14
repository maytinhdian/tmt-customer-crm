<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Quotation\Presentation\Admin\Controller;


final class QuoteController
{
    /** Hook id của màn hình Customers */
    private static string $hook_suffix = '';

    /** Gọi 1 lần ở bootstrap (file chính) */
    public static function register(): void
    {
        // 1) Menu + Screen Options
        // add_action('admin_menu', [self::class, 'register_admin_menu'], 20);

        // 2) Controller: admin-post actions (đăng ký luôn tại đây)
        // CustomerController::register();
    }
}

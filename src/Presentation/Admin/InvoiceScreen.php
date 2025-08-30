<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin;

use TMT\CRM\Infrastructure\Security\Capability;

final class InvoiceScreen
{
    public const PAGE_SLUG = 'tmt-crm-invoices';

    public static function on_load(): void {}
    public static function dispatch(): void
    {
        if (! current_user_can(Capability::QUOTE_READ)) {
            wp_die(__('Bạn không có quyền truy cập trang này.', 'tmt-crm'), 403);
        }
        echo '<div class="wrap"><h1>Hoá đơn</h1><p>Đang phát triển…</p></div>';
    }
}

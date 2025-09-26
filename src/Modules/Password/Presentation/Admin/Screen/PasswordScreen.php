<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Presentation\Admin\Screen;

use TMT\CRM\Shared\Presentation\Support\View;
use TMT\CRM\Modules\Password\Presentation\Admin\ListTable\PasswordListTable;

final class PasswordScreen
{
    public const PAGE_SLUG = 'tmt-crm-passwords';

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền.', 'tmt-crm'));
        }

        echo '<div class="wrap"><h1 class="wp-heading-inline">' . esc_html__('Passwords', 'tmt-crm') . '</h1>';

        $table = new PasswordListTable();
        $table->prepare_items();

        echo '<form method="get">';
        foreach (['page'] as $keep) {
            if (isset($_GET[$keep])) {
                printf('<input type="hidden" name="%s" value="%s" />', esc_attr($keep), esc_attr((string)$_GET[$keep]));
            }
        }
        $table->search_box(__('Tìm kiếm', 'tmt-crm'), 'tmt-password');
        $table->display();
        echo '</form>';

        // Form add/edit: luôn dùng View helper
        if (method_exists(View::class, 'render_admin_module')) {
            View::render_admin_module('password', 'form', [
                // truyền dữ liệu cần thiết
            ]);
        }

        echo '</div>';
    }
}

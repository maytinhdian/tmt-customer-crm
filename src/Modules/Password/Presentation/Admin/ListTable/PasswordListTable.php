<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Password\Presentation\Admin\ListTable;

use WP_List_Table;
use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Modules\Password\Application\Services\PasswordService;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class PasswordListTable extends WP_List_Table
{
    private PasswordService $service;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'password',
            'plural'   => 'passwords',
            'ajax'     => false,
        ]);
        $this->service = Container::get(PasswordService::class);
    }

    public function get_columns(): array
    {
        return [
            'cb'       => '<input type="checkbox" />',
            'title'    => __('Tiêu đề', 'tmt-crm'),
            'username' => __('Tài khoản', 'tmt-crm'),
            'url'      => __('URL', 'tmt-crm'),
            'owner'    => __('Owner', 'tmt-crm'),
            'date'     => __('Ngày', 'tmt-crm'),
        ];
    }

    protected function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="ids[]" value="%d" />', $item->id);
    }

    protected function column_title($item)
    {
        $view = add_query_arg(['page' => $_REQUEST['page'], 'action' => 'edit', 'id' => $item->id], admin_url('admin.php'));
        $reveal = wp_nonce_url(add_query_arg(['action' => 'reveal', 'id' => $item->id]), 'reveal-password-' . $item->id);
        $actions = [
            'edit'   => '<a href="' . esc_url($view) . '">' . __('Sửa', 'tmt-crm') . '</a>',
            'reveal' => '<a href="' . esc_url($reveal) . '">' . __('Hiện mật khẩu', 'tmt-crm') . '</a>',
        ];
        return sprintf('<strong>%s</strong> %s', esc_html($item->title), $this->row_actions($actions));
    }

    public function prepare_items(): void
    {
        $per_page = 20;
        $page = max(1, (int)($_GET['paged'] ?? 1));
        $filters = [
            'q' => sanitize_text_field($_GET['s'] ?? ''),
        ];
        $data = $this->service->list($filters, $page, $per_page);

        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = $data['items'];
        $this->set_pagination_args([
            'total_items' => $data['total'],
            'per_page'    => $per_page,
        ]);
    }

    protected function column_default($item, $column_name)
    {
        return match ($column_name) {
            'username' => esc_html($item->username ?? ''),
            'url'      => $item->url ? '<a href="' . esc_url($item->url) . '" target="_blank">' . esc_html($item->url) . '</a>' : '',
            'owner'    => esc_html(get_userdata($item->owner_id)->display_name ?? ''),
            'date'     => esc_html(mysql2date('Y-m-d H:i', $item->updated_at)),
            default    => '',
        };
    }
}

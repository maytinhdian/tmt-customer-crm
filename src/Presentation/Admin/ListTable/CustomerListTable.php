<?php

namespace TMT\CRM\Presentation\Admin\ListTable;

use TMT\CRM\Shared\Container;
use TMT\CRM\Infrastructure\Security\Capability;

defined('ABSPATH') || exit;

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Bảng danh sách khách hàng (WP_List_Table)
 */
final class CustomerListTable extends \WP_List_Table
{
    private array $items_data = [];
    private int $total = 0;
    private int $per_page = 20;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'customer',
            'plural'   => 'customers',
            'ajax'     => false,
        ]);
    }

    public function get_columns(): array
    {
        return [
            'cb'      => '<input type="checkbox" />',
            'id'      => 'ID',
            'name'    => __('Tên khách hàng', 'tmt-crm'),
            'email'   => __('Email', 'tmt-crm'),
            'phone'   => __('Điện thoại', 'tmt-crm'),
            'company' => __('Công ty', 'tmt-crm'),
        ];
    }

    public function get_sortable_columns(): array
    {
        return [
            'id'   => ['id', false],
            'name' => ['name', false],
            'company'=>['name',false]
        ];
    }

    protected function column_cb($item): string
    {
        return sprintf(
            '<input type="checkbox" name="ids[]" value="%d" />',
            (int)$item['id']
        );
    }

    public function column_default($item, $column_name)
    {
        return esc_html($item[$column_name] ?? '');
    }

    public function column_name($item): string
    {
        $id  = (int)$item['id'];
        $txt = esc_html($item['name'] ?? '');

        $actions = [];

        if (current_user_can(Capability::EDIT)) {
            $edit_url = add_query_arg([
                'page'   => 'tmt-crm-customers',
                'action' => 'edit',
                'id'     => $id,
            ], admin_url('admin.php'));

            $actions['edit'] = sprintf('<a href="%s">%s</a>', esc_url($edit_url), esc_html__('Sửa', 'tmt-crm'));
        }

        if (current_user_can(Capability::DELETE)) {
            $del_url = wp_nonce_url(
                add_query_arg([
                    'action' => 'tmt_crm_customer_delete',
                    'id'     => $id,
                ], admin_url('admin-post.php')),
                'tmt_crm_customer_delete_' . $id
            );
            $actions['delete'] = sprintf(
                '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($del_url),
                esc_js(__('Xác nhận xoá khách hàng?', 'tmt-crm')),
                esc_html__('Xoá', 'tmt-crm')
            );
        }

        return sprintf('<strong>%s</strong> %s', $txt, $this->row_actions($actions));
    }

    public function get_bulk_actions(): array
    {
        $actions = [];
        if (current_user_can(Capability::DELETE)) {
            $actions['bulk-delete'] = __('Xoá đã chọn', 'tmt-crm');
        }
        return $actions;
    }

    /**
     * Gọi trước render: nạp dữ liệu + tính phân trang
     */
    public function prepare_items(): void
    {
        $svc = Container::get('customer-service');

        // per_page từ Screen Options
        $this->per_page = (int) get_user_meta(get_current_user_id(), 'tmt_crm_customers_per_page', true);
        if ($this->per_page <= 0) $this->per_page = 20;

        $current_page = $this->get_pagenum();
        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : '';
        $order   = isset($_GET['order']) ? strtoupper(sanitize_text_field($_GET['order'])) : 'DESC';
        if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'DESC';

        $filters = [
            'keyword'  => sanitize_text_field($_GET['s'] ?? ''),
            'type'     => sanitize_key($_GET['type'] ?? ''),
            'owner_id' => isset($_GET['owner']) ? absint($_GET['owner']) : null,
            // nếu repo hỗ trợ sort, truyền xuống:
            'orderby'  => $orderby ?: null,
            'order'    => $order ?: null,
        ];

        $data = $svc->list_customers($current_page, $this->per_page, $filters);
        $items = $data['items'] ?? [];
        $this->total = (int)($data['total'] ?? 0);

        // Chuẩn hoá về array cho dễ render (an toàn kiểu)
        $this->items_data = array_map(function ($c) {
            if (is_array($c)) {
                return [
                    'id'      => (int)   ($c['id'] ?? 0),
                    'name'    => (string)($c['name'] ?? ''),
                    'email'   => (string)($c['email'] ?? ''),
                    'phone'   => (string)($c['phone'] ?? ''),
                    // fallback company_name nếu service trả tên công ty theo key khác
                    'company' => (string)($c['company'] ?? $c['company_name'] ?? ''),
                ];
            }

            // Mặc định: CustomerDTO (object)
            return [
                'id'      => (int)   ($c->id ?? 0),
                'name'    => (string)($c->name ?? ''),
                'email'   => (string)($c->email ?? ''),
                'phone'   => (string)($c->phone ?? ''),
                // fallback company_name nếu DTO dùng thuộc tính khác
                'company' => (string)($c->company ?? $c->company_name ?? ''),
            ];
        }, $items);

        $this->_column_headers = [
            $this->get_columns(),
            [], // hidden
            $this->get_sortable_columns(),
        ];

        $this->items = $this->items_data;

        $this->set_pagination_args([
            'total_items' => $this->total,
            'per_page'    => $this->per_page,
            'total_pages' => (int) ceil($this->total / $this->per_page),
        ]);
    }

    /**
     * Xử lý bulk action xoá (trả về danh sách id đã chọn)
     */
    public function get_selected_ids_for_bulk_delete(): array
    {
        if ($this->current_action() !== 'bulk-delete') return [];
        $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : [];
        return array_values(array_filter(array_map('absint', $ids)));
    }
}

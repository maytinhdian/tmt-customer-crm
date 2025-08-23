<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\ListTable;

use TMT\CRM\Shared\Container;

defined('ABSPATH') || exit;

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Bảng danh sách Companies (WP_List_Table)
 */
final class CompanyListTable extends \WP_List_Table
{
    private array $items_data = [];
    private int $total_items = 0;
    private int $per_page = 20;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'company',
            'plural'   => 'companies',
            'ajax'     => false,
        ]);
    }

    /**
     * Cột hiển thị
     */
    public function get_columns(): array
    {
        return [
            'cb'         => '<input type="checkbox" />',
            'id'         => 'ID',
            'name'       => 'Tên công ty',
            'tax_code'   => 'Mã số thuế',
            'email'      => 'Email',
            'phone'      => 'Điện thoại',
            'address'    => 'Địa chỉ',
            'created_at' => 'Tạo lúc',
            'updated_at' => 'Cập nhật',
        ];
    }

    /**
     * Cột sort được
     */
    protected function get_sortable_columns(): array
    {
        return [
            'id'         => ['id', false],
            'name'       => ['name', false],
            'tax_code'   => ['tax_code', false],
            'email'      => ['email', false],
            'phone'      => ['phone', false],
            'created_at' => ['created_at', false],
            'updated_at' => ['updated_at', false],
        ];
    }

    /**
     * Bulk actions
     */
    protected function get_bulk_actions(): array
    {
        return [
            'bulk-delete' => 'Xoá đã chọn',
        ];
    }

    /**
     * Checkbox
     */
    protected function column_cb($item): string
    {
        return sprintf('<input type="checkbox" name="ids[]" value="%d" />', (int)$item['id']);
    }

    /**
     * Cột mặc định
     */
    protected function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'tax_code':
            case 'email':
            case 'phone':
            case 'created_at':
            case 'updated_at':
                return esc_html((string)($item[$column_name] ?? ''));
            case 'address':
                $addr = (string)($item['address'] ?? '');
                $addr = wp_strip_all_tags($addr);
                return esc_html(mb_strimwidth($addr, 0, 80, '…'));
            default:
                return '';
        }
    }

    /**
     * Cột name có row actions
     */
    protected function column_name($item): string
    {
        $id   = (int)$item['id'];
        $name = esc_html((string)$item['name']);

        $edit_url = add_query_arg([
            'page'   => 'tmt-crm-companies',
            'action' => 'edit',
            'id'     => $id,
        ], admin_url('admin.php'));

        $delete_url = wp_nonce_url(
            add_query_arg([
                'action' => 'tmt_crm_company_delete',
                'id'     => $id,
            ], admin_url('admin-post.php')),
            'tmt_crm_company_delete_' . $id
        );

        $actions = [
            'edit'   => sprintf('<a href="%s">Sửa</a>', esc_url($edit_url)),
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'Xoá công ty này?\')">Xoá</a>', esc_url($delete_url)),
        ];

        return sprintf('<strong><a href="%s">%s</a></strong> %s', esc_url($edit_url), $name, $this->row_actions($actions));
    }

    /**
     * Gọi trước render: nạp dữ liệu + tính phân trang
     */
    public function prepare_items(): void
    {
        $svc = Container::get('company-service');

        // per_page từ Screen Options
        $this->per_page = (int) get_user_meta(get_current_user_id(), 'tmt_crm_companies_per_page', true);
        if ($this->per_page <= 0) $this->per_page = 20;

        $current_page = $this->get_pagenum();

        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'id';
        $order   = isset($_GET['order']) ? strtoupper(sanitize_text_field($_GET['order'])) : 'DESC';
        if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'DESC';

        $filters = [
            'keyword' => sanitize_text_field($_GET['s'] ?? ''),
            'orderby' => $orderby,
            'order'   => $order,
        ];

        $result = $svc->get_paged($current_page, $this->per_page, $filters);

        // Convert DTO -> array để hiển thị nhanh
        $this->items_data = array_map(function ($dto) {
            return [
                'id'         => $dto->id,
                'name'       => $dto->name,
                'tax_code'   => $dto->tax_code,
                'email'      => $dto->email,
                'phone'      => $dto->phone,
                'address'    => $dto->address,
                'created_at' => $dto->created_at,
                'updated_at' => $dto->updated_at,
            ];
        }, $result['items']);

        $this->total_items = (int)$result['total'];

        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns(),
        ];

        $this->items = $this->items_data;

        $this->set_pagination_args([
            'total_items' => $this->total_items,
            'per_page'    => $this->per_page,
            'total_pages' => (int)ceil($this->total_items / $this->per_page),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\ListTable;

use WP_List_Table;
use TMT\CRM\Application\DTO\CompanyContactViewDTO;

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * CompanyContactsListTable
 * - Theo hướng #2: Chỉ RENDER dữ liệu đã được tổ hợp sẵn (CompanyContactViewDTO[])
 * - Không tự query repo/service bên trong ListTable.
 */
final class CompanyContactsListTable extends WP_List_Table
{
    /** @var CompanyContactViewDTO[] */
    private array $items_view = [];

    private int $company_id;
    private int $total_items = 0;
    private int $per_page    = 20;

    /**
     * @param CompanyContactViewDTO[] $items_view
     */
    public function __construct(array $items_view, int $total_items, int $per_page, int $company_id)
    {
        parent::__construct([
            'singular' => 'company_contact',
            'plural'   => 'company_contacts',
            'ajax'     => false,
        ]);

        $this->items_view  = $items_view;
        $this->total_items = $total_items;
        $this->per_page    = $per_page;
        $this->company_id = $company_id;
    }

    /**
     * Định nghĩa cột
     */
    public function get_columns(): array
    {
        return [
            'cb'            => '<input type="checkbox" />',
            'full_name'     => __('Họ tên', 'tmt-crm'),
            'role'          => __('Vai trò', 'tmt-crm'),
            'position'      => __('Chức vụ', 'tmt-crm'),
            'phone'         => __('Số điện thoại', 'tmt-crm'), // từ tmt_crm_customers
            'email'         => __('Email', 'tmt-crm'),         // từ tmt_crm_customers
            'owner'         => __('Người phụ trách', 'tmt-crm'), // tên + (#ID)
            'owner_contact' => __('Liên hệ phụ trách', 'tmt-crm'), // sđt/email phụ trách
            'period'        => __('Hiệu lực', 'tmt-crm'),
            'is_primary'    => __('Chính', 'tmt-crm'),
        ];
    }

    /**
     * Khai báo cột có thể sort (nếu muốn bật UI sort)
     * Lưu ý: sort thực tế nên xử lý ở tầng Repo/Service theo cột thuộc company_contacts.
     */
    protected function get_sortable_columns(): array
    {
        return [
            'role'       => ['role', false],
            'position'   => ['position', false],
            'is_primary' => ['is_primary', false],
            'period'     => ['start_date', false], // sort theo start_date ở backend nếu cần
        ];
    }

    /**
     * Checkbox mỗi dòng
     * @param CompanyContactViewDTO $item
     */
    public function column_cb($item): string
    {
        return sprintf(
            '<input type="checkbox" name="contact_ids[]" value="%d" />',
            (int) $item->id
        );
    }

    /**
     * Cột Họ tên (thường muốn có row actions)
     * @param CompanyContactViewDTO $item
     */
    protected function column_full_name($item): string
    {
        $name = esc_html($item->full_name);
        // Ví dụ row actions (sửa/xoá…) – tuỳ bạn nối URL thực tế:
        $actions = [];

        // $edit_url = add_query_arg([...], admin_url('admin.php'));
        // $actions['edit'] = sprintf('<a href="%s">%s</a>', esc_url($edit_url), esc_html__('Sửa', 'tmt-crm'));

        return $name . $this->row_actions($actions);
    }

    /**
     * Render mặc định cho các cột còn lại
     * @param CompanyContactViewDTO $item
     */
    protected function column_default($item, $column_name): string
    {
        switch ($column_name) {
            case 'role':
                return esc_html($item->role ?? '—');

            case 'position':
                return esc_html($item->position ?? '—');

            case 'phone':
                return esc_html($item->phone ?? '—');

            case 'email':
                return esc_html($item->email ?? '—');

            case 'owner':
                // Hiển thị: "Tên (#ID)" hoặc "—"
                $owner_name = $item->owner_name ?: '—';
                $owner_id   = $item->owner_id ? (' #' . (int) $item->owner_id) : '';
                return esc_html($owner_name . $owner_id);

            case 'owner_contact':
                // Ghép sđt/email của người phụ trách (nếu có)
                $parts = array_filter([$item->owner_phone, $item->owner_email]);
                return esc_html(!empty($parts) ? implode(' | ', $parts) : '—');

            case 'period':
                $from = $item->start_date ?: '—';
                $to   = $item->end_date ?: __('hiện tại', 'tmt-crm');
                return esc_html($from . ' → ' . $to);

            case 'is_primary':
                return $item->is_primary ? '✓' : '—';
        }

        return '';
    }

    /**
     * Chuẩn bị dữ liệu cho WP_List_Table
     */
    public function prepare_items(): void
    {
        $columns  = $this->get_columns();
        $hidden   = []; // có thể tuỳ biến qua Screen Options
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items           = $this->items_view;

        $this->set_pagination_args([
            'total_items' => $this->total_items,
            'per_page'    => $this->per_page,
            'total_pages' => max(1, (int) ceil($this->total_items / $this->per_page)),
        ]);
    }
}

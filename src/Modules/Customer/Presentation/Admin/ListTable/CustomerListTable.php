<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Presentation\Admin\ListTable;

use TMT\CRM\Shared\Container;
use TMT\CRM\Infrastructure\Security\Capability;
use TMT\CRM\Modules\Customer\Presentation\Admin\Screen\CustomerScreen;

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
            'updated_at' => __('Ngày chỉnh sửa', 'tmt-crm'),
            'owner'   => __('Người phụ trách', 'tmt-crm'),
        ];
    }

    public function get_sortable_columns(): array
    {
        return [
            'id'   => ['id', false],
            'name' => ['name', false],
            'phone' => ['phone', false],
            'updated_at' => ['updated_at', false],
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
        switch ($column_name) {
            case 'id':
                return (int)($item['id'] ?? 0);

            case 'name':
                return esc_html($item['name'] ?? '');

            case 'email':
                $email = trim((string)($item['email'] ?? ''));
                return $email !== '' ? '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>' : '<span class="tmtcrm-muted">(chưa gán)</span>';

            case 'phone':
                $phone = trim((string)($item['phone'] ?? ''));
                return $phone !== '' ? esc_html($phone) : '<span class="tmtcrm-muted">(chưa gán)</span>';

            case 'owner':
                // Ưu tiên chuỗi đã render sẵn trong items_data['owner']; fallback sang owner_id
                if (!empty($item['owner'])) {
                    return esc_html($item['owner']);
                }
                if (!empty($item['owner_id'])) {
                    $u = get_user_by('id', (int)$item['owner_id']);
                    return $u ? esc_html($u->display_name ?: $u->user_login) : '<span class="tmtcrm-muted">(không rõ)</span>';
                }
                return '<span class="tmtcrm-muted">(chưa gán)</span>';

            case 'updated_at':
                $ts = $item['updated_at'] ?? '';
                return $ts ? esc_html(mysql2date('d/m/Y H:i', $ts)) : '<span class="tmtcrm-muted">(chưa gán)</span>';

            default:
                // Debug an toàn
                return esc_html(print_r($item, true));
        }
    }


    public function column_name($item): string
    {
        $id  = (int)$item['id'];
        $txt = esc_html($item['name'] ?? '');

        $actions = [];

        if (current_user_can(Capability::CUSTOMER_UPDATE_ANY, $id)) {
            $edit_url = add_query_arg([
                'page'   => 'tmt-crm-customers',
                'action' => 'edit',
                'id'     => $id,
            ], admin_url('admin.php'));

            $actions['edit'] = sprintf('<a href="%s">%s</a>', esc_url($edit_url), esc_html__('Sửa', 'tmt-crm'));
        }

        if (current_user_can(Capability::CUSTOMER_DELETE, $id)) {
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
        if (current_user_can(Capability::CUSTOMER_DELETE)) {
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

        // Lấy per-page từ Screen Options (KHÔNG đặt trong __construct vì lúc đó screen chưa sẵn)
        $this->per_page = $this->get_items_per_page(
            CustomerScreen::OPTION_PER_PAGE,
            20
        );

        $current_page = $this->get_pagenum();


        // Whitelist orderby
        $allowed_orderby = ['id', 'name', 'company'];
        $orderby_raw = isset($_GET['orderby']) ? sanitize_key((string) $_GET['orderby']) : '';
        $orderby = in_array($orderby_raw, $allowed_orderby, true) ? $orderby_raw : null;

        $order_raw = isset($_GET['order']) ? strtoupper(sanitize_text_field((string) $_GET['order'])) : 'DESC';
        $order = in_array($order_raw, ['ASC', 'DESC'], true) ? $order_raw : 'DESC';

        $filters = [
            'keyword'  => sanitize_text_field((string)($_GET['s'] ?? '')),
            'type'     => sanitize_key((string)($_GET['type'] ?? '')),
            'owner_id' => isset($_GET['owner']) ? absint((string) $_GET['owner']) : null,
            'orderby'  => $orderby,
            'order'    => $order,
        ];

        $data  = $svc->list_customers($current_page, $this->per_page, $filters);
        $items = $data['items'] ?? [];
        $this->total = (int)($data['total'] ?? 0);

        $this->items_data = array_map(function ($c): array {
            // object (DTO)
            $ownerId = (int)($c->owner_id ?? 0);
            return [
                'id'      => (int)   ($c->id ?? 0),
                'name'    => (string)($c->name ?? ''),
                'email'   => (string)($c->email ?? ''),
                'phone'   => (string)($c->phone ?? ''),
                'owner'    => $ownerId ? get_the_author_meta('display_name', $ownerId) : '',
                'updated_at' => (string)($c->updated_at ?? ''), // ← thêm
            ];
        }, $items);

        $screen   = get_current_screen();
        $hidden   = get_hidden_columns($screen); // ✅ cột bị ẩn theo user prefs

        $this->_column_headers = [
            $this->get_columns(),
            $hidden,
            [],
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

    public static function default_hidden_columns(array $hidden, \WP_Screen $screen): array
    {
        if ($screen->id === 'tmt-crm_page_' . CustomerScreen::PAGE_SLUG) {
            $hidden = array_unique(array_merge($hidden, ['email', 'owner']));
        }
        return $hidden;
    }
}

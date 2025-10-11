<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\Customer\Presentation\Admin\ListTable;

use TMT\CRM\Shared\Container\Container;
use TMT\CRM\Core\Capabilities\Domain\Capability;
use TMT\CRM\Modules\Customer\Presentation\Admin\Screen\CustomerScreen;
use TMT\CRM\Modules\Customer\Application\DTO\CustomerDTO;
use TMT\CRM\Modules\Customer\Domain\Repositories\CustomerRepositoryInterface;

defined('ABSPATH') || exit;

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * CustomerListTable
 * - Phân trang, sort, filter, bulk actions cho bảng Customers.
 * - Không truy vấn DB trực tiếp: ủy quyền cho Repository.
 */
final class CustomerListTable extends \WP_List_Table
{
    public const NONCE_BULK   = 'tmt_crm_customer_bulk_nonce';
    public const ACTION_BULK  = 'bulk-delete';

    /** @var CustomerRepositoryInterface */
    private CustomerRepositoryInterface $repo;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'customer',
            'plural'   => 'customers',
            'ajax'     => false,
        ]);

        /** @var CustomerRepositoryInterface $repo */
        $this->repo = Container::get(CustomerRepositoryInterface::class);
    }

    /** ===== Columns ===== */
    public function get_columns(): array
    {
        return [
            'cb'         => '<input type="checkbox" />',
            'id'         => __('ID', 'tmt-crm'),
            'name'       => __('Name', 'tmt-crm'),
            'email'      => __('Email', 'tmt-crm'),
            'phone'      => __('Phone', 'tmt-crm'),
            'owner'      => __('Owner', 'tmt-crm'),
            'created_at' => __('Created', 'tmt-crm'),
            'updated_at' => __('Updated', 'tmt-crm'),
        ];
    }

    public function get_sortable_columns(): array
    {
        return [
            'id'         => ['id', true],
            'name'       => ['name', false],
            'email'      => ['email', false],
            'created_at' => ['created_at', true],
            'updated_at' => ['updated_at', false],
        ];
    }

    public function get_hidden_columns(): array
    {
        // Trả về mảng rỗng để Screen Options quyết định.
        return [];
    }

    public function column_cb($item): string
    {
        $id = (int)($item->id ?? 0);
        return sprintf('<input type="checkbox" name="ids[]" value="%d" />', $id);
    }

    /** Primary column = name (hiện row actions) */
    protected function get_primary_column_name(): string
    {
        return 'name';
    }

    public function column_name($item): string
    {
        /** @var CustomerDTO $item */
        $id    = (int)$item->id;
        $name  = esc_html($item->name ?: sprintf(__('(No name) #%d', 'tmt-crm'), $id));
        $page  = CustomerScreen::PAGE_SLUG;
        $view = isset($_GET['view']) ? sanitize_key((string)$_GET['view']) : 'active';
        $is_deleted = ($view === 'deleted') || !empty($item->deleted_at);
        // $actions = [];

        // ----- DELETED VIEW: primary = Restore -----
        if ($is_deleted) {
            $actions = [];

            if (current_user_can(Capability::CUSTOMER_DELETE)) {
                // Restore
                $restore_action = CustomerScreen::ACTION_RESTORE;             // vd: 'tmt_crm_customer_restore'
                $restore_nonce  = $restore_action . ':' . $id;
                $restore_url    = wp_nonce_url(
                    admin_url('admin-post.php?action=' . $restore_action . '&id=' . $id),
                    $restore_nonce
                );
                $actions['restore'] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url($restore_url),
                    esc_html__('Restore', 'tmt-crm')
                );

                // Delete permanently
                $purge_action = CustomerScreen::ACTION_HARD_DELETE;                 // vd: 'tmt_crm_customer_purge'
                $purge_nonce  = $purge_action . ':' . $id;
                $purge_url    = wp_nonce_url(
                    admin_url('admin-post.php?action=' . $purge_action . '&id=' . $id),
                    $purge_nonce
                );
                $actions['delete'] = sprintf(
                    '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\')">%s</a>',
                    esc_url($purge_url),
                    esc_attr__('Delete this customer permanently?', 'tmt-crm'),
                    esc_html__('Delete permanently', 'tmt-crm')
                );
            }

            // Name → link trực tiếp to RESTORE
            $primary = sprintf(
                '<strong><a class="row-title" href="%s">%s</a></strong>',
                esc_url($restore_url ?? '#'),
                $name
            );
            return $primary . ' ' . $this->row_actions($actions);
        }
        // ----- ACTIVE/ALL: như cũ (View/Edit/Delete=soft) -----
        if (current_user_can(Capability::CUSTOMER_READ)) {
            $view_url = admin_url(sprintf('admin.php?page=%s&view=%d', $page, $id));
            $actions['view'] = sprintf('<a href="%s">%s</a>', esc_url($view_url), esc_html__('View', 'tmt-crm'));
        }

        if (current_user_can(Capability::CUSTOMER_UPDATE)) {
            $edit_url = admin_url(sprintf('admin.php?page=%s&action=edit&id=%d', $page, $id));
            $actions['edit'] = sprintf('<a href="%s">%s</a>', esc_url($edit_url), esc_html__('Edit', 'tmt-crm'));
        }

        if (current_user_can(Capability::CUSTOMER_DELETE)) {
            $delete_url = wp_nonce_url(
                admin_url('admin-post.php?action=' . CustomerScreen::ACTION_SOFT_DELETE . '&id=' . $id),
                CustomerScreen::NONCE_SOFT_DELETE . $id
            );
            $actions['delete'] = sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\')">%s</a>',
                esc_url($delete_url),
                esc_attr__('Delete this customer?', 'tmt-crm'),
                esc_html__('Delete', 'tmt-crm')
            );
        }

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a></strong> %s',
            esc_url(admin_url(sprintf('admin.php?page=%s&action=edit&id=%d', $page, $id))),
            $name,
            $this->row_actions($actions)
        );
    }

    public function column_email($item): string
    {
        /** @var CustomerDTO $item */
        return $item->email ? sprintf('<a href="mailto:%s">%s</a>', esc_attr($item->email), esc_html($item->email)) : '&mdash;';
    }

    public function column_phone($item): string
    {
        /** @var CustomerDTO $item */
        $phone = trim((string)$item->phone);
        return $phone !== '' ? esc_html($phone) : '&mdash;';
    }



    public function column_owner($item): string
    {
        /** @var CustomerDTO $item */
        if (!$item->owner_id) return '&mdash;';

        $uid   = (int)$item->owner_id;
        $user  = get_user_by('ID', $uid);
        if (!$user) return (string)$uid;

        $phone = get_user_meta($uid, 'phone', true);
        if (!$phone) $phone = get_user_meta($uid, 'billing_phone', true);

        $label = trim($user->display_name);
        $label .= $phone ? ' (' . preg_replace('/\s+/', '', (string)$phone) . ')' : '';

        return esc_html($label ?: (string)$uid);
    }

    public function column_created_at($item): string
    {
        /** @var CustomerDTO $item */
        return $item->created_at ? esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $item->created_at)) : '&mdash;';
    }

    public function column_updated_at($item): string
    {
        /** @var CustomerDTO $item */
        return $item->updated_at ? esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $item->updated_at)) : '&mdash;';
    }

    public function column_default($item, $column_name): string
    {
        $val = $item->{$column_name} ?? '';
        return $val !== '' ? esc_html((string)$val) : '&mdash;';
    }

    /** ===== Filters/Search/Views ===== */

    protected function get_views(): array
    {
        $base = remove_query_arg(['paged', 'view']);
        $cur  = isset($_GET['view']) ? sanitize_key((string)$_GET['view']) : 'active';

        // Đếm tổng
        $active_total = (int)$this->repo->count_all(['only_trashed' => false, 'with_trashed' => false]);
        $all_total    = (int)$this->repo->count_all(['with_trashed' => true]);
        $trash_total  = (int)$this->repo->count_all(['only_trashed' => true]);

        return [
            'active' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url(add_query_arg('view', 'active', $base)),
                $cur === 'active' ? 'current' : '',
                esc_html__('Active', 'tmt-crm'),
                $active_total
            ),
            'deleted' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url(add_query_arg('view', 'deleted', $base)),
                $cur === 'deleted' ? 'current' : '',
                esc_html__('Deleted', 'tmt-crm'),
                $trash_total
            ),
            'all' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                esc_url(add_query_arg('view', 'all', $base)),
                $cur === 'all' ? 'current' : '',
                esc_html__('All', 'tmt-crm'),
                $all_total
            ),
        ];
    }
    public function no_items(): void
    {
        $view = isset($_GET['view']) ? sanitize_key((string)$_GET['view']) : 'active';
        if ($view === 'deleted') {
            esc_html_e('No customers in trash.', 'tmt-crm');
        } elseif ($view === 'all') {
            esc_html_e('No customers found.', 'tmt-crm');
        } else {
            esc_html_e('No active customers.', 'tmt-crm');
        }
    }

    /** ===== Bulk actions ===== */
    protected function get_bulk_actions(): array
    {
        $view = isset($_GET['view']) ? sanitize_key((string)$_GET['view']) : 'active';

        if ($view === 'deleted') {
            $actions = [];
            if (current_user_can(Capability::CUSTOMER_DELETE)) {
                $actions['restore'] = __('Restore', 'tmt-crm');
                $actions['delete']  = __('Delete permanently', 'tmt-crm');
            }
            return $actions;
        }

        // active/all
        if (current_user_can(Capability::CUSTOMER_DELETE)) {
            return ['trash' => __('Move to trash', 'tmt-crm')];
        }
        return [];
    }

    /**
     * Xử lý bulk hành động tại chỗ (trả về mảng ID hợp lệ).
     * Gợi ý: Controller có thể đảm nhận; ở đây cho trải nghiệm liền mạch.
     */
    public function process_bulk_action(): void
    {
        if ($this->current_action() !== self::ACTION_BULK) {
            return;
        }
        check_admin_referer(self::NONCE_BULK);

        if (!current_user_can(Capability::CUSTOMER_DELETE)) {
            wp_die(__('You are not allowed to delete customers.', 'tmt-crm'), 403);
        }

        $ids = isset($_POST['ids']) ? array_map('absint', (array)$_POST['ids']) : [];
        $ids = array_values(array_filter($ids));

        if ($ids) {
            // Repo xoá mềm/hard tuỳ implement
            foreach ($ids as $id) {
                $this->repo->delete($id);
            }
            add_settings_error('tmt_crm_customers', 'deleted', sprintf(__('Deleted %d customers.', 'tmt-crm'), count($ids)), 'updated');
        }
    }

    /** ===== Data/prepare ===== */

    public function prepare_items(): void
    {
        $view = isset($_GET['view']) ? sanitize_key((string)$_GET['view']) : 'active';

        $current_page = max(1, (int)($_GET['paged'] ?? 1));
        $orderby      = sanitize_text_field((string)($_GET['orderby'] ?? 'id'));
        $order        = strtolower((string)($_GET['order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $keyword       = isset($_REQUEST['s']) ? trim((string)$_REQUEST['s']) : '';

        $per_page = (int)get_user_meta(get_current_user_id(), self::get_per_page_option_name(), true);
        if ($per_page <= 0) {
            $per_page = 20;
        }


        $filters = [
            'orderby'       => $orderby,
            'order'         => $order,
            'keyword'        => $keyword,
            'only_trashed'  => ($view === 'deleted'),
            'with_trashed'  => ($view === 'all'),
        ];

        // Lấy dữ liệu: repo chịu trách nhiệm áp điều kiện & hạn chế injection
        $items = $this->repo->list_paginated($current_page, $per_page, $filters);
        $total = $this->repo->count_all($filters);

        $this->items = $items;

        $this->_column_headers = [
            $this->get_columns(),
            $this->get_hidden_columns(),
            $this->get_sortable_columns(),
            $this->get_primary_column_name(),
        ];

        $this->set_pagination_args([
            'total_items' => (int)$total,
            'per_page'    => $per_page,
            'total_pages' => (int)ceil($total / $per_page),
        ]);
    }

    /** ===== Helpers ===== */

    /** Tên option per-page riêng cho màn này */
    public static function get_per_page_option_name(): string
    {
        return CustomerScreen::OPTION_PER_PAGE; // 'tmt_crm_customers_per_page'
    }

    /** Mặc định ẩn bớt các cột dài */
    public static function default_hidden_columns(array $hidden, \WP_Screen $screen): array
    {
        if ($screen->id === 'tmt-crm_page_' . CustomerScreen::PAGE_SLUG) {
            $hidden = array_unique(array_merge($hidden, ['email', 'owner']));
        }
        return $hidden;
    }

    /** Render form bulk nonce */
    public static function render_bulk_nonce(): void
    {
        wp_nonce_field(self::NONCE_BULK);
    }
    // Trong CustomerListTable
    public function single_row($item): void
    {
        /** @var \TMT\CRM\Modules\Customer\Application\DTO\CustomerDTO $item */
        $classes = [];

        // hàng alternate của WP giữ nguyên
        static $row_class = '';
        $row_class = ('alternate' === $row_class) ? '' : 'alternate';
        if ($row_class) $classes[] = $row_class;

        // nếu bị xoá mềm → thêm class để CSS bắt
        if (!empty($item->deleted_at)) {
            $classes[] = 'tmt-soft-deleted';
        }

        echo '<tr class="' . esc_attr(implode(' ', $classes)) . '">';
        $this->single_row_columns($item);
        echo '</tr>';
    }
}

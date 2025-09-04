<?php

declare(strict_types=1);

namespace TMT\CRM\Presentation\Admin\ListTable;

use TMT\CRM\Shared\Container;
use TMT\CRM\Application\DTO\CompanyContactDTO;
use TMT\CRM\Domain\Repositories\CompanyContactRepositoryInterface;
use TMT\CRM\Domain\Repositories\CustomerRepositoryInterface;
use TMT\CRM\Domain\Repositories\CompanyRepositoryInterface;
use TMT\CRM\Domain\Repositories\UserRepositoryInterface;

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Bảng liên hệ công ty (CompanyContacts)
 * - Tuân thủ DIP: chỉ phụ thuộc Interfaces trong Domain.
 */
final class CompanyContactsListTable extends \WP_List_Table
{
    private int $company_id;
    private int $per_page = 20;

    /** View-model: mảng đã enrich sẵn để render nhanh */
    private array $items_view = [];
    private int $total_items = 0;

    public function __construct(int $company_id)
    {
        parent::__construct([
            'singular' => 'contact',
            'plural'   => 'contacts',
            'ajax'     => false,
        ]);
        $this->company_id = $company_id;
    }

    public function get_columns(): array
    {
        return [
            'cb'         => '<input type="checkbox" />',
            'full_name'  => __('Họ tên', 'tmt-crm'),         // từ customer
            'role'       => __('Vai trò', 'tmt-crm'),
            'position'   => __('Chức vụ', 'tmt-crm'),
            'phone'      => __('Điện thoại', 'tmt-crm'),      // từ customer
            'email'      => __('Email', 'tmt-crm'),           // từ customer
            'period'     => __('Hiệu lực', 'tmt-crm'),
            'is_primary' => __('Chính', 'tmt-crm'),
            'owner'      => __('Người phụ trách', 'tmt-crm'), // owner_name của company
            'actions'    => __('Thao tác', 'tmt-crm'),
        ];
    }

    protected function get_sortable_columns(): array
    {
        return [
            'full_name' => ['full_name', false],
            'role'      => ['role', false],
            'is_primary' => ['is_primary', false],
            'period'    => ['start_date', false],
        ];
    }

    public function prepare_items(): void
    {
        // per_page có thể đọc từ Screen Options (nếu bạn đã set), fallback 20
        $this->per_page = (int) get_user_meta(get_current_user_id(), 'tmt_crm_company_contacts_per_page', true) ?: 20;
        $current_page   = max(1, $this->get_pagenum());

        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'id';
        $order   = isset($_GET['order']) ? strtoupper(sanitize_text_field($_GET['order'])) : 'DESC';
        if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'DESC';

        $filters = [
            'keyword'     => sanitize_text_field($_GET['s'] ?? ''),
            'active_only' => !empty($_GET['active']) ? 1 : 0,
            'orderby'     => $orderby,
            'order'       => $order,
        ];

        /** @var CompanyContactRepositoryInterface $contact_repo */
        $contact_repo  = Container::get('company-contact-repo');

        /** @var CustomerRepositoryInterface $customer_repo */
        $customer_repo = Container::get('customer-repo');

        /** @var CompanyRepositoryInterface $company_repo */
        $company_repo  = Container::get('company-repo');

        /** @var UserRepositoryInterface $user_repo */
        $user_repo     = Container::get('user-repo');

        // 1) Lấy data thô (DTO) theo DIP
        // $items        = $contact_repo->find_paged_by_company($this->company_id, $current_page, $this->per_page, $filters);
        // $this->total_items = $contact_repo->count_by_company($this->company_id, $filters);

        // 2) Lấy owner 1 lần (DIP)
        $owner_id   = $company_repo->get_owner_id($this->company_id);
        $owner_name = $owner_id ? ($user_repo->get_display_name($owner_id) ?? null) : null;

        // 3) Bulk-load Customer theo DIP
        $customer_ids = [];
        foreach ($items as $dto) {
            if ($dto instanceof CompanyContactDTO && !empty($dto->customer_id)) {
                $customer_ids[] = (int) $dto->customer_id;
            }
        }
        $customers_map = $customer_repo->find_by_ids(array_values(array_unique($customer_ids)));

        // 4) Enrich sang view-model
        $this->items_view = array_map(function (CompanyContactDTO $d) use ($customers_map, $owner_id, $owner_name): array {
            $c    = $customers_map[$d->customer_id] ?? null;
            $name = $d->full_name ?: ($d->contact_name ?? ($c ? $c->full_name : ''));
            if ($name === '' && $d->customer_id) {
                $name = '#' . (int) $d->customer_id;
            }

            $period = ($d->start_date ?: '—') . ' → ' . ($d->end_date ?: __('hiện tại', 'tmt-crm'));

            return [
                'id'         => (int) $d->id,
                'customer_id' => (int) ($d->customer_id ?? 0),
                'full_name'  => (string) $name,
                'role'       => (string) ($d->role ?? ''),
                'position'   => (string) ($d->title ?? ''),
                'phone'      => (string) ($c->phone ?? $d->phone ?? ''),
                'email'      => (string) ($c->email ?? $d->email ?? ''),
                'period'     => $period,
                'is_primary' => (bool) $d->is_primary,
                'owner_id'   => $owner_id,
                'owner_name' => $owner_name,
            ];
        }, $items);

        // 5) Header & paging
        $screen = get_current_screen();
        $hidden = get_hidden_columns($screen);

        $this->_column_headers = [
            $this->get_columns(),
            $hidden,
            [],
            $this->get_sortable_columns(),
        ];

        $this->items = $this->items_view;

        $this->set_pagination_args([
            'total_items' => $this->total_items,
            'per_page'    => $this->per_page,
            'total_pages' => (int) ceil($this->total_items / $this->per_page),
        ]);
    }

    /* ========== Columns ========== */

    protected function column_cb($item): string
    {
        return sprintf('<input type="checkbox" name="contact_ids[]" value="%d" />', (int) $item['id']);
    }

    protected function column_full_name($item): string
    {
        $name = esc_html($item['full_name'] ?? '—');

        $actions = [];
        if (!empty($item['customer_id'])) {
            $profile_url   = add_query_arg(['page' => 'tmt-crm-customers', 'action' => 'edit', 'id' => (int)$item['customer_id']], admin_url('admin.php'));
            $actions['view'] = sprintf('<a href="%s">%s</a>', esc_url($profile_url), esc_html__('Mở hồ sơ', 'tmt-crm'));
        }
        return $actions ? ($name . $this->row_actions($actions)) : $name;
    }

    protected function column_role($item): string
    {
        return esc_html($item['role'] ?? '');
    }

    protected function column_position($item): string
    {
        return esc_html($item['position'] ?? '');
    }

    protected function column_phone($item): string
    {
        return esc_html($item['phone'] ?? '');
    }

    protected function column_email($item): string
    {
        $email = (string) ($item['email'] ?? '');
        return $email ? sprintf('<a href="mailto:%1$s">%1$s</a>', esc_html($email)) : '—';
    }

    protected function column_period($item): string
    {
        return esc_html($item['period'] ?? '');
    }

    protected function column_is_primary($item): string
    {
        return !empty($item['is_primary']) ? '✔' : '—';
    }

    protected function column_owner($item): string
    {
        if (!empty($item['owner_name'])) {
            return esc_html((string) $item['owner_name']);
        }
        if (!empty($item['owner_id'])) {
            return '#' . (int) $item['owner_id'];
        }
        return '—';
    }

    protected function column_actions($item): string
    {
        $company_id = $this->company_id;
        $contact_id = (int) $item['id'];

        // Ví dụ action “Gỡ”
        $detach_url = wp_nonce_url(
            admin_url('admin-post.php?action=tmt_crm_company_contact_detach&company_id=' . $company_id . '&contact_id=' . $contact_id),
            'tmt_crm_company_contact_detach_' . $company_id . '_' . $contact_id
        );

        $actions = [
            'detach' => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
                esc_url($detach_url),
                esc_attr__('Gỡ liên hệ khỏi công ty?', 'tmt-crm'),
                esc_html__('Gỡ', 'tmt-crm')
            ),
        ];

        return $this->row_actions($actions);
    }
}

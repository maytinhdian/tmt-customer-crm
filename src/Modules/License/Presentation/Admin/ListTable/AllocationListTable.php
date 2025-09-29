<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\ListTable;

use WP_List_Table;
use TMT\CRM\Modules\License\Application\DTO\CredentialSeatAllocationDTO;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class AllocationListTable extends WP_List_Table
{
    /** @var CredentialSeatAllocationDTO[] */
    private array $items_data = [];

    public function __construct()
    {
        parent::__construct([
            'singular' => 'allocation',
            'plural'   => 'allocations',
            'ajax'     => false,
        ]);
    }

    public function set_data(array $items): void
    {
        $this->items_data = $items;
    }

    public function get_columns(): array
    {
        return [
            'beneficiary' => __('Beneficiary', 'tmt-crm'),
            'seat'        => __('Seat (used/quota)', 'tmt-crm'),
            'status'      => __('Status', 'tmt-crm'),
            'invited_at'  => __('Invited', 'tmt-crm'),
            'accepted_at' => __('Accepted', 'tmt-crm'),
            'note'        => __('Note', 'tmt-crm'),
        ];
    }

    protected function column_beneficiary($item): string
    {
        $display = esc_html($item->beneficiary_type);
        if ($item->beneficiary_email) {
            $display .= ' - ' . esc_html($item->beneficiary_email);
        } elseif ($item->beneficiary_id) {
            $display .= ' #' . (int)$item->beneficiary_id;
        }

        $edit_url = add_query_arg([
            'page' => 'tmt-crm-licenses-edit',
            'id'   => (int)$item->credential_id,
            'tab'  => 'allocations',
            'edit_allocation' => (int)$item->id,
        ], admin_url('admin.php'));

        $del_url = wp_nonce_url(add_query_arg([
            'action'        => 'tmt_license_allocation_delete',
            'credential_id' => (int)$item->credential_id,
            'id'            => (int)$item->id,
        ], admin_url('admin-post.php')), 'tmt_license_allocation_delete');

        $actions = [
            'edit'   => sprintf('<a href="%s">%s</a>', esc_url($edit_url), __('Edit', 'tmt-crm')),
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'Delete allocation?\')">%s</a>', esc_url($del_url), __('Delete', 'tmt-crm')),
        ];

        return sprintf('<strong>%s</strong> %s', $display, $this->row_actions($actions));
    }

    protected function column_seat($item): string
    {
        return sprintf('%d / %d', (int)$item->seat_used, (int)$item->seat_quota);
    }

    protected function column_status($item): string
    {
        return esc_html($item->status);
    }

    protected function column_invited_at($item): string
    {
        return esc_html($item->invited_at ?? '');
    }

    protected function column_accepted_at($item): string
    {
        return esc_html($item->accepted_at ?? '');
    }

    protected function column_note($item): string
    {
        return esc_html($item->note ?? '');
    }

    public function no_items(): void
    {
        _e('No allocations.', 'tmt-crm');
    }

    public function prepare_items(): void
    {
        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = $this->items_data;
    }
}

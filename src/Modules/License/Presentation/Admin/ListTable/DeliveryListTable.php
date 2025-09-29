<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\ListTable;

use WP_List_Table;
use TMT\CRM\Modules\License\Application\DTO\CredentialDeliveryDTO;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class DeliveryListTable extends WP_List_Table
{
    /** @var CredentialDeliveryDTO[] */
    private array $items_data = [];
    private int $credential_id = 0;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'delivery',
            'plural'   => 'deliveries',
            'ajax'     => false,
        ]);
    }

    public function set_data(int $credential_id, array $items): void
    {
        $this->credential_id = $credential_id;
        $this->items_data = $items;
    }

    public function get_columns(): array
    {
        return [
            'to'          => __('Delivered To', 'tmt-crm'),
            'channel'     => __('Channel', 'tmt-crm'),
            'delivered_at' => __('Delivered At', 'tmt-crm'),
            'note'        => __('Note', 'tmt-crm'),
        ];
    }

    protected function column_to($item): string
    {
        $who = '';
        if ($item->delivered_to_email) {
            $who = $item->delivered_to_email;
        } elseif ($item->delivered_to_contact_id) {
            $who = 'contact #' . (int)$item->delivered_to_contact_id;
        } elseif ($item->delivered_to_customer_id) {
            $who = 'customer #' . (int)$item->delivered_to_customer_id;
        } elseif ($item->delivered_to_company_id) {
            $who = 'company #' . (int)$item->delivered_to_company_id;
        } else {
            $who = '-';
        }

        $del_url = wp_nonce_url(add_query_arg([
            'action'        => 'tmt_license_delivery_delete',
            'credential_id' => (int)$this->credential_id,
            'id'            => (int)$item->id,
        ], admin_url('admin-post.php')), 'tmt_license_delivery_delete');

        $actions = [
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'Delete delivery?\')">%s</a>', esc_url($del_url), __('Delete', 'tmt-crm')),
        ];

        return '<strong>' . esc_html($who) . '</strong> ' . $this->row_actions($actions);
    }

    protected function column_channel($item): string
    {
        return esc_html($item->channel ?? 'email');
    }

    protected function column_delivered_at($item): string
    {
        return esc_html($item->delivered_at ?? '');
    }

    protected function column_note($item): string
    {
        return esc_html($item->delivery_note ?? '');
    }

    public function no_items(): void
    {
        _e('No deliveries recorded.', 'tmt-crm');
    }

    public function prepare_items(): void
    {
        $this->_column_headers = [$this->get_columns(), [], []];
        $this->items = $this->items_data;
    }
}

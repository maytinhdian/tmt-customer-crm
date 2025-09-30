<?php

declare(strict_types=1);

namespace TMT\CRM\Modules\License\Presentation\Admin\ListTable;

use WP_List_Table;
use TMT\CRM\Modules\License\Application\DTO\CredentialDTO;
use TMT\CRM\Modules\License\Application\Services\CryptoService;
use \TMT\CRM\Shared\Presentation\Support\AdminPostHelper;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

final class CredentialListTable extends WP_List_Table
{
    /** @var CredentialDTO[] */
    private array $items_data = [];
    private int $total = 0;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'credential',
            'plural'   => 'credentials',
            'ajax'     => false,
        ]);
    }

    public function set_data(array $items, int $total): void
    {
        $this->items_data = $items;
        $this->total = $total;
    }

    public function get_columns(): array
    {
        return [
            'cb'        => '<input type="checkbox" />',
            'number'    => __('Number', 'tmt-crm'),
            'label'     => __('Label', 'tmt-crm'),
            'type'      => __('Type', 'tmt-crm'),
            'status'    => __('Status', 'tmt-crm'),
            'seats'     => __('Seats (used/total)', 'tmt-crm'),
            'expires_at' => __('Expires', 'tmt-crm'),
            'license'   => __('License Key', 'tmt-crm'),
        ];
    }

    protected function get_sortable_columns(): array
    {
        return [
            'number' => ['number', true],
            'label'  => ['label', false],
            'expires_at' => ['expires_at', false],
        ];
    }

    protected function column_cb($item): string
    {
        return sprintf('<input type="checkbox" name="ids[]" value="%d" />', (int)$item->id);
    }

    public function column_number($item): string
    {
        // $edit_url = add_query_arg(['page' => 'tmt-crm-licenses-edit', 'id' => (int)$item->id], admin_url('admin.php'));
        $edit_url = AdminPostHelper::url(
            'tmt_crm_license_open_form',
            [
                'id' => (int) $item->id,
                'view' => 'edit',
                'tab'  => 'general',
            ]
        );

        // $edit_url = add_query_arg([
        //     'page' => \TMT\CRM\Modules\License\Presentation\Admin\Screen\LicenseScreen::PAGE_SLUG,
        //     'view' => 'edit',
        //     'id'   => (int)$item->id,
        //     'tab'  => 'general',
        // ], admin_url('admin.php'));

        $del_url  = wp_nonce_url(add_query_arg([
            'action' => 'tmt_license_delete',
            'id'     => (int)$item->id,
        ], admin_url('admin-post.php')), 'tmt_license_delete');

        $actions = [
            'edit'   => sprintf('<a href="%s">%s</a>', esc_url($edit_url), __('Edit', 'tmt-crm')),
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'Delete?\')">%s</a>', esc_url($del_url), __('Delete', 'tmt-crm')),
        ];

        return sprintf(
            '<strong><a href="%s">%s</a></strong> %s',
            esc_url($edit_url),
            esc_html((string)$item->number),
            $this->row_actions($actions)
        );
    }

    public function column_label($item): string
    {
        return esc_html((string)$item->label);
    }

    public function column_type($item): string
    {
        return esc_html((string)$item->type);
    }

    public function column_status($item): string
    {
        return esc_html((string)$item->status);
    }

    public function column_seats($item): string
    {
        $total = $item->seats_total ?? '-';
        // P0: chưa tính used thực; để '-' hoặc bạn có thể tính ở Screen và gán vào $item->seats_used
        $used  = property_exists($item, 'seats_used') ? (int)$item->seats_used : '-';
        return sprintf('%s / %s', esc_html((string)$used), esc_html((string)$total));
    }

    public function column_expires_at($item): string
    {
        return esc_html((string)($item->expires_at ?? ''));
    }

    protected function column_license($item): string
    {
        return (string)($item->secret_mask ?? '');
    }


    private function render_secret_cell(int $credential_id, string $cipher, string $field_key): string
    {
        if ($cipher === '') {
            return '<span style="color:#888">—</span>';
        }
        $crypto = new CryptoService();
        $plain  = $crypto->decrypt_secret($cipher) ?? '';
        if ($plain === '') {
            // nếu không giải mã được, hiển thị placeholder
            return '<span style="color:#888">[invalid secret]</span>';
        }

        $masked = $this->mask_tail($plain, 6); // lộ 6 ký tự cuối
        $eye    = '<span class="dashicons dashicons-visibility" style="vertical-align:middle;"></span>';

        // data-field dùng giá trị “thân thiện” (controller đã map sang *_encrypted)
        $btn = sprintf(
            '<a href="#" class="tmt-reveal-secret tmt-reveal-secret-list" data-id="%d" data-field="%s" style="margin-left:6px; font-size:12px;">%s</a>',
            $credential_id,
            esc_attr($field_key),
            $eye
        );

        return sprintf('<span class="tmt-secret-text">%s</span>%s', esc_html($masked), $btn);
    }

    /** Mask mọi ký tự trừ `visible` ký tự cuối */
    private function mask_tail(string $s, int $visible = 6): string
    {
        $len = function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
        if ($len <= $visible) {
            return $s;
        }
        $suffix = function_exists('mb_substr') ? mb_substr($s, $len - $visible, $visible, 'UTF-8') : substr($s, -$visible);
        return str_repeat('*', $len - $visible) . $suffix;
    }
    public function no_items(): void
    {
        _e('No credentials found.', 'tmt-crm');
    }

    public function prepare_items(): void
    {
        $per_page = 20;
        $current_page = $this->get_pagenum();

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];

        $this->items = $this->items_data;

        $this->set_pagination_args([
            'total_items' => $this->total,
            'per_page'    => $per_page,
            'total_pages' => max(1, (int)ceil($this->total / $per_page)),
        ]);
    }
}
